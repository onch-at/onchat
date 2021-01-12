<?php

declare(strict_types=1);

namespace app\core\service;

use app\core\Result;
use app\model\User as UserModel;
use app\core\util\Str as StrUtil;
use app\core\oss\Client as OssClient;
use app\model\Chatroom as ChatroomModel;
use app\model\UserInfo as UserInfoModel;
use app\model\ChatMember as ChatMemberModel;
use app\model\ChatRequest as ChatRequestModel;
use app\model\ChatSession as ChatSessionModel;
use think\facade\Db;

class Chat
{
    /** 群聊人数已满 */
    const CODE_PEOPLE_NUM_FULL = 1;
    /** 附加消息过长 */
    const CODE_REASON_LONG = 2;

    /** 附加消息最大长度 */
    const REASON_MAX_LENGTH = 50;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_REASON_LONG  => '附加消息长度不能大于' . self::REASON_MAX_LENGTH . '位字符'
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

        $peopleNum = ChatMemberModel::where('chatroom_id', '=', $chatroomId)->count();
        // 人数超出上限
        if ($peopleNum >= $chatroom->max_people_num) {
            return new Result(self::CODE_PEOPLE_NUM_FULL, '聊天室人数已满！');
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

        $ossClient = OssClient::getInstance();
        $stylename = OssClient::getThumbnailImgStylename();

        $info = UserInfoModel::where('user_info.user_id', '=', $applicant)
            ->field([
                'user_info.nickname as applicantNickname',
                'user_info.avatar as applicantAvatarThumbnail'
            ])
            ->find()
            ->toArray();
        $info['applicantAvatarThumbnail'] = $ossClient->signImageUrl($info['applicantAvatarThumbnail'], $stylename);

        $chatroom = ChatroomModel::field('chatroom.name as chatroomName')
            ->find($chatroomId)
            ->toArray();

        return Result::success($request->toArray() + $info + $chatroom);
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
            ->join('user_info', 'chat_request.applicant_id = user_info.user_id')
            ->join('chatroom', 'chatroom.id = chat_request.chatroom_id')
            ->where('chat_member.user_id', '=', $userId)
            ->where(function ($query) {
                $query->whereOr([
                    ['chat_member.role', '=', ChatMemberModel::ROLE_HOST],
                    ['chat_member.role', '=', ChatMemberModel::ROLE_MANAGE],
                ]);
            })
            ->field([
                'user_info.nickname as applicantNickname',
                'user_info.avatar as applicantAvatarThumbnail',
                'chatroom.name as chatroomName',
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
