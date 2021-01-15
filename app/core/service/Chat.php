<?php

declare(strict_types=1);

namespace app\core\service;

use app\core\Result;
use think\facade\Db;
use app\model\User as UserModel;
use app\core\util\Sql as SqlUtil;
use app\core\util\Str as StrUtil;
use app\core\oss\Client as OssClient;
use app\core\util\Redis as RedisUtil;
use app\model\Chatroom as ChatroomModel;
use app\model\UserInfo as UserInfoModel;
use app\model\ChatMember as ChatMemberModel;
use app\model\ChatRequest as ChatRequestModel;
use app\model\ChatSession as ChatSessionModel;

class Chat
{
    /** 群聊人数已满 */
    const CODE_PEOPLE_NUM_FULL = 1;
    /** 附加消息过长 */
    const CODE_REASON_LONG = 2;
    /** 请求已被处理 */
    const CODE_REQUEST_HANDLED = 3;

    /** 附加消息最大长度 */
    const REASON_MAX_LENGTH = 50;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_REASON_LONG  => '附加消息长度不能大于' . self::REASON_MAX_LENGTH . '位字符',
        self::CODE_REASON_LONG => '聊天室人数已满！',
        self::CODE_REQUEST_HANDLED => '该请求已被处理！'
    ];

    /**
     * 邀请好友入群
     *
     * @param integer $inviter 邀请人ID
     * @param integer $chatroomId 邀请进入的群聊ID
     * @param array $chatroomIdList 受邀人的私聊聊天室ID列表
     * @return Result
     */
    public static function invite(int $inviter, int $chatroomId, array $chatroomIdList): Result
    {
        // 找到这个聊天室
        $chatroom = ChatroomModel::join('chat_member', 'chat_member.chatroom_id = chatroom.id')
            ->where([
                ['chatroom.id', '=', $chatroomId],
                ['chat_member.user_id', '=', $inviter],
                ['chat_member.role', '=', ChatMemberModel::ROLE_HOST]
            ])->field('chatroom.*')->find();

        if (!$chatroom) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 人数超出上限
        if (Chatroom::isPeopleNumFull($chatroomId)) {
            return new Result(self::CODE_PEOPLE_NUM_FULL, self::MSG[self::CODE_PEOPLE_NUM_FULL]);
        }

        // 找到正确的私聊聊天室ID（防止客户端乱传ID）
        $chatroomIdList = ChatMemberModel::join('chatroom', 'chatroom.id = chat_member.chatroom_id')->where([
            ['chat_member.user_id', '=', $inviter],
            ['chatroom.id', 'IN', $chatroomIdList],
            ['chatroom.type', '=', ChatroomModel::TYPE_PRIVATE_CHAT],
        ])->column('chatroom.id');

        return Result::success($chatroomIdList);
    }


    /**
     * 申请加入聊天室
     *
     * @param integer $applicant 申请人ID
     * @param integer $chatroomId 聊天室ID
     * @param string $reason 申请原因
     * @return Result
     */
    public static function request(int $applicant, int $chatroomId, string $reason = null): Result
    {
        // 如果已经是聊天室成员了
        if (Chatroom::isMember($chatroomId, $applicant)) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 如果人数已满
        if (Chatroom::isPeopleNumFull($chatroomId)) {
            return new Result(self::CODE_PEOPLE_NUM_FULL, '聊天室人数已满！');
        }

        // 如果剔除空格后长度为零，则直接置空
        if ($reason && mb_strlen(StrUtil::trimAll($reason), 'utf-8') == 0) {
            $reason = null;
        }

        // 如果附加消息长度超出
        if ($reason && mb_strlen($reason, 'utf-8') > self::REASON_MAX_LENGTH) {
            return new Result(self::CODE_REASON_LONG, self::MSG[self::CODE_REASON_LONG]);
        }

        // 先找找之前有没有申请过
        $request = ChatRequestModel::where([
            ['applicant_id', '=', $applicant],
            ['chatroom_id', '=', $chatroomId],
            ['status', '<>', ChatRequestModel::STATUS_AGREE]
        ])->find();

        $timestamp = time() * 1000;

        if ($request) {
            $request->status = ChatRequestModel::STATUS_WAIT;
            $request->request_reason = $reason;
            $request->reject_reason = null;
            // 清空已读列表
            $request->readed_list = [];
            $request->update_time = $timestamp;
            $request->save();
        } else {
            $request = ChatRequestModel::create([
                'chatroom_id'    => $chatroomId,
                'applicant_id'   => $applicant,
                'status'         => ChatRequestModel::STATUS_WAIT,
                'request_reason' => $reason,
                'readed_list'    => [],
                'create_time'    => $timestamp,
                'update_time'    => $timestamp,
            ]);
        }

        // 显示群主/管理员的聊天室通知会话
        ChatSessionModel::where('type', '=', ChatSessionModel::TYPE_CHATROOM_NOTICE)
            ->where('user_id', 'IN', function ($query) use ($chatroomId) {
                $query->table('chat_member')
                    ->where('chatroom_id', '=', $chatroomId)
                    ->where(function ($query) {
                        $query->whereOr([
                            ['role', '=', ChatMemberModel::ROLE_HOST],
                            ['role', '=', ChatMemberModel::ROLE_MANAGE],
                        ]);
                    })->field('user_id');
            })
            ->update([
                'chat_session.update_time' => SqlUtil::rawTimestamp(),
                'chat_session.visible' => true
            ]);

        $ossClient = OssClient::getInstance();
        $stylename = OssClient::getThumbnailImgStylename();

        $info = UserInfoModel::where('user_info.user_id', '=', $applicant)
            ->field([
                'user_info.nickname AS applicantNickname',
                'user_info.avatar AS applicantAvatarThumbnail'
            ])
            ->find()
            ->toArray();
        $info['applicantAvatarThumbnail'] = $ossClient->signImageUrl($info['applicantAvatarThumbnail'], $stylename);

        $chatroom = ChatroomModel::field('chatroom.name AS chatroomName')
            ->find($chatroomId)
            ->toArray();

        return Result::success($request->toArray() + $info + $chatroom);
    }

    /**
     * 同意入群申请
     *
     * @param integer $id 请求ID
     * @param integer $handler 处理人ID
     * @param integer $handlerUsername 处理人的名字
     * @return Result
     */
    public static function agree(int $id, int $handler)
    {
        $request = ChatRequestModel::join('chat_member', 'chat_request.chatroom_id = chat_member.chatroom_id')
            ->join('user_info applicant', 'chat_request.applicant_id = applicant.user_id')
            ->join('chatroom', 'chatroom.id = chat_request.chatroom_id')
            ->where([
                'chat_request.id' => $id,
                'chat_member.user_id' => $handler
            ])
            ->where(function ($query) {
                $query->whereOr([
                    ['chat_member.role', '=', ChatMemberModel::ROLE_HOST],
                    ['chat_member.role', '=', ChatMemberModel::ROLE_MANAGE],
                ]);
            })
            ->field([
                'applicant.nickname AS applicantNickname',
                'applicant.avatar AS applicantAvatarThumbnail',
                'chatroom.name AS chatroomName',
                'chatroom.avatar AS chatroomAvatarThumbnail',
                'chat_request.*'
            ])
            ->find();

        if (!$request) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 已被处理
        if ($request->handler_id) {
            return new Result(self::CODE_REQUEST_HANDLED, self::MSG[self::CODE_REQUEST_HANDLED]);
        }

        $chatroomId = $request->chatroom_id;

        // 人数超出上限
        if (Chatroom::isPeopleNumFull($chatroomId)) {
            return new Result(self::CODE_PEOPLE_NUM_FULL, self::MSG[self::CODE_PEOPLE_NUM_FULL]);
        }

        // 启动事务
        Db::startTrans();
        try {
            // 如果自己还未读
            if (!in_array($handler, $request->readed_list)) {
                $request->readed_list[] = $handler;
            }

            $request->handler_id = $handler;
            $request->status = ChatRequestModel::STATUS_AGREE;
            $request->update_time = time() * 1000;
            $request->save();

            $result = Chatroom::addMember($chatroomId, $request->applicant_id, $request->applicantNickname);
            if ($result->code !== Result::CODE_SUCCESS) {
                Db::rollback();
                return $result;
            }

            $ossClient = OssClient::getInstance();
            $stylename = OssClient::getThumbnailImgStylename();

            $chatSession = $result->data;

            // 补充一些信息
            $chatSession['title'] = $request->chatroomName;
            $chatSession['avatarThumbnail'] = $ossClient->signImageUrl($request->chatroomAvatarThumbnail, $stylename);
            $chatSession['data']['chatroomType'] = ChatroomModel::TYPE_GROUP_CHAT;

            $request->applicantAvatarThumbnail = $ossClient->signImageUrl($request->applicantAvatarThumbnail, $stylename);

            $request = $request->toArray();

            $request['handlerNickname'] = RedisUtil::getUserByUserId($handler)['username'];

            unset($request['chatroomAvatarThumbnail']);

            Db::commit();
            return Result::success([$request, $chatSession]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * 已读所有入群请求
     *
     * @return Result
     */
    public static function readed(): Result
    {
        $userId = User::getId();
        ChatRequestModel::whereRaw("!JSON_CONTAINS(readed_list, JSON_ARRAY({$userId}))")
            ->update([
                'readed_list' => Db::raw("JSON_ARRAY_APPEND(readed_list, '$', {$userId})")
            ]);

        ChatSessionModel::update(['unread' => 0], [
            'user_id' => $userId,
            'type' => ChatSessionModel::TYPE_CHATROOM_NOTICE
        ]);

        return Result::success();
    }

    /**
     * 通过请求ID获取我收到的入群请求
     *
     * @param integer $id
     * @return Result
     */
    public static function getReceiveRequestById(int $id): Result
    {
        $userId = User::getId();

        $request = ChatRequestModel::join('chat_member', 'chat_request.chatroom_id = chat_member.chatroom_id')
            ->join('user_info applicant', 'chat_request.applicant_id = applicant.user_id')
            ->leftJoin('user_info handler', 'chat_request.handler_id = handler.user_id')
            ->join('chatroom', 'chatroom.id = chat_request.chatroom_id')
            ->where([
                'chat_request.id' => $id,
                'chat_member.user_id' => $userId
            ])
            ->where(function ($query) {
                $query->whereOr([
                    ['chat_member.role', '=', ChatMemberModel::ROLE_HOST],
                    ['chat_member.role', '=', ChatMemberModel::ROLE_MANAGE],
                ]);
            })
            ->field([
                'applicant.nickname AS applicantNickname',
                'applicant.avatar AS applicantAvatarThumbnail',
                'handler.nickname AS handlerNickname',
                'chatroom.name AS chatroomName',
                'chat_request.*'
            ])
            ->find();

        if (!$request) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        $ossClient = OssClient::getInstance();
        $stylename = OssClient::getThumbnailImgStylename();
        $request->applicantAvatarThumbnail = $ossClient->signImageUrl($request->applicantAvatarThumbnail, $stylename);

        return Result::success($request->toArray());
    }

    /**
     * 获取我收到的入群申请
     *
     * @return Result
     */
    public static function getReceiveRequests(): Result
    {
        $userId = User::getId();
        $ossClient = OssClient::getInstance();
        $stylename = OssClient::getThumbnailImgStylename();

        $data = ChatRequestModel::join('chat_member', 'chat_request.chatroom_id = chat_member.chatroom_id')
            ->join('user_info applicant', 'chat_request.applicant_id = applicant.user_id')
            ->leftJoin('user_info handler', 'chat_request.handler_id = handler.user_id')
            ->join('chatroom', 'chatroom.id = chat_request.chatroom_id')
            ->where('chat_member.user_id', '=', $userId)
            ->where(function ($query) {
                $query->whereOr([
                    ['chat_member.role', '=', ChatMemberModel::ROLE_HOST],
                    ['chat_member.role', '=', ChatMemberModel::ROLE_MANAGE],
                ]);
            })
            ->field([
                'applicant.nickname AS applicantNickname',
                'applicant.avatar AS applicantAvatarThumbnail',
                'handler.nickname AS handlerNickname',
                'chatroom.name AS chatroomName',
                'chat_request.*'
            ])
            ->select()
            ->toArray();

        foreach ($data as $key => $value) {
            $data[$key]['applicantAvatarThumbnail'] = $ossClient->signImageUrl($value['applicantAvatarThumbnail'], $stylename);
        }

        return Result::success($data);
    }
}
