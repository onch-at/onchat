<?php

declare(strict_types=1);

namespace app\service;

use app\core\Result;
use app\core\storage\Storage;
use app\facade\ChatroomService;
use app\facade\ChatSessionService;
use app\facade\UserService;
use app\model\ChatMember as ChatMemberModel;
use app\model\ChatRequest as ChatRequestModel;
use app\model\Chatroom as ChatroomModel;
use app\model\ChatSession as ChatSessionModel;
use app\model\UserInfo as UserInfoModel;
use app\utils\Str as StrUtils;
use think\facade\Db;

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
        self::CODE_PEOPLE_NUM_FULL => '聊天室人数已满！',
        self::CODE_REASON_LONG     => '附加消息长度不能大于' . self::REASON_MAX_LENGTH . '位字符',
        self::CODE_REQUEST_HANDLED => '该请求已被处理！',
    ];

    /**
     * 邀请好友入群.
     *
     * @param int   $inviter        邀请人ID
     * @param int   $chatroomId     邀请进入的群聊ID
     * @param array $chatroomIdList 受邀人的私聊聊天室ID列表
     *
     * @return Result
     */
    public function invite(int $inviter, int $chatroomId, array $chatroomIdList): Result
    {
        // 找到这个聊天室
        $chatroom = ChatroomModel::join('chat_member', 'chat_member.chatroom_id = chatroom.id')
            ->where([
                ['chatroom.id', '=', $chatroomId],
                ['chatroom.type', '=', ChatroomModel::TYPE_GROUP_CHAT],
                ['chat_member.user_id', '=', $inviter],
            ])->field('chatroom.*')->find();

        if (!$chatroom) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        // 人数超出上限
        if (ChatroomService::isPeopleNumFull($chatroomId)) {
            return Result::create(self::CODE_PEOPLE_NUM_FULL, self::MSG[self::CODE_PEOPLE_NUM_FULL]);
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
     * 申请加入聊天室.
     *
     * @param int    $requester  申请人ID
     * @param int    $chatroomId 聊天室ID
     * @param string $reason     申请原因
     *
     * @return Result
     */
    public function request(int $requester, int $chatroomId, string $reason = null): Result
    {
        // 如果已经是聊天室成员了
        if (ChatroomService::isMember($chatroomId, $requester)) {
            return Result::create(Result::CODE_PARAM_ERROR, '你已加入该聊天室！');
        }

        // 如果人数已满
        if (ChatroomService::isPeopleNumFull($chatroomId)) {
            return Result::create(self::CODE_PEOPLE_NUM_FULL, '聊天室人数已满！');
        }

        // 如果剔除空格后长度为零，则直接置空
        if ($reason && StrUtils::isEmpty($reason)) {
            $reason = null;
        }

        // 如果附加消息长度超出
        if ($reason && StrUtils::length($reason) > self::REASON_MAX_LENGTH) {
            return Result::create(self::CODE_REASON_LONG, self::MSG[self::CODE_REASON_LONG]);
        }

        // 先找找之前有没有申请过
        $request = ChatRequestModel::where([
            ['requester_id', '=', $requester],
            ['chatroom_id', '=', $chatroomId],
            ['status', '<>', ChatRequestModel::STATUS_AGREE],
        ])->find();

        $timestamp = time() * 1000;

        if ($request) {
            $request->status         = ChatRequestModel::STATUS_WAIT;
            $request->request_reason = $reason;
            $request->reject_reason  = null;
            $request->readed_list    = [$requester];
            $request->handler_id     = null;
            $request->update_time    = $timestamp;
            $request->save();
        } else {
            $request = ChatRequestModel::create([
                'chatroom_id'    => $chatroomId,
                'requester_id'   => $requester,
                'status'         => ChatRequestModel::STATUS_WAIT,
                'request_reason' => $reason,
                'readed_list'    => [$requester],
                'create_time'    => $timestamp,
                'update_time'    => $timestamp,
            ]);
        }

        ChatSessionService::showChatroomNotice($chatroomId, $requester);

        $storage = Storage::create();

        $info = UserInfoModel::where('user_info.user_id', '=', $requester)
            ->field([
                'user_info.nickname AS requesterNickname',
                'user_info.avatar AS requesterAvatarThumbnail',
            ])
            ->find();
        $info->requesterAvatarThumbnail = $storage->getThumbnailUrl($info->requesterAvatarThumbnail);

        $chatroom = ChatroomModel::field([
            'chatroom.name AS chatroomName',
            'chatroom.avatar AS chatroomAvatar',
        ])->find($chatroomId);

        $chatroom->chatroomAvatarThumbnail = $storage->getThumbnailUrl($chatroom->chatroomAvatar);
        $chatroom->chatroomAvatar          = $storage->getUrl($chatroom->chatroomAvatar);

        return Result::success($request->toArray() + $info->toArray() + $chatroom->toArray());
    }

    /**
     * 同意入群申请.
     *
     * @param int $id      请求ID
     * @param int $handler 处理人ID
     *
     * @return Result
     */
    public function agree(int $id, int $handler): Result
    {
        $request = ChatRequestModel::join('chat_member', 'chat_request.chatroom_id = chat_member.chatroom_id')
            ->join('user_info requester', 'chat_request.requester_id = requester.user_id')
            ->join('chatroom', 'chatroom.id = chat_request.chatroom_id')
            ->where([
                'chat_request.id'     => $id,
                'chat_member.user_id' => $handler,
            ])
            ->where(function ($query) {
                $query->whereOr([
                    ['chat_member.role', '=', ChatMemberModel::ROLE_HOST],
                    ['chat_member.role', '=', ChatMemberModel::ROLE_MANAGE],
                ]);
            })
            ->field([
                'requester.nickname AS requesterNickname',
                'requester.avatar AS requesterAvatarThumbnail',
                'chatroom.name AS chatroomName',
                'chatroom.avatar AS chatroomAvatar',
                'chat_request.*',
            ])
            ->find();

        if (!$request) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        // 已被处理
        if ($request->handler_id) {
            return Result::create(self::CODE_REQUEST_HANDLED, self::MSG[self::CODE_REQUEST_HANDLED]);
        }

        $chatroomId = $request->chatroom_id;

        // 人数超出上限
        if (ChatroomService::isPeopleNumFull($chatroomId)) {
            return Result::create(self::CODE_PEOPLE_NUM_FULL, self::MSG[self::CODE_PEOPLE_NUM_FULL]);
        }

        // 启动事务
        Db::startTrans();

        try {
            $readedList = array_filter($request->readed_list, function ($o) use ($request) {
                return $o !== $request->requester_id;
            });

            // 如果自己还未读
            if (!in_array($handler, $request->readed_list)) {
                $readedList[] = $handler;
            }

            $request->readed_list = array_values($readedList);
            $request->handler_id  = $handler;
            $request->status      = ChatRequestModel::STATUS_AGREE;
            $request->update_time = time() * 1000;
            $request->save();

            $result = ChatroomService::addMember($chatroomId, $request->requester_id, $request->requesterNickname);
            if ($result->isFail()) {
                Db::rollback();

                return $result;
            }

            ChatSessionService::showChatroomNotice($request->chatroom_id, $request->requester_id);

            $storage = Storage::create();

            $chatSession = $result->data;
            $avatar      = $request->chatroomAvatar;

            // 补充一些信息
            $chatSession['title']                = $request->chatroomName;
            $chatSession['avatarThumbnail']      = $storage->getThumbnailUrl($avatar);
            $chatSession['data']['chatroomType'] = ChatroomModel::TYPE_GROUP_CHAT;

            $request->requesterAvatarThumbnail = $storage->getThumbnailUrl($request->requesterAvatarThumbnail);
            $request->chatroomAvatar           = $storage->getUrl($avatar);
            $request->chatroomAvatarThumbnail  = $chatSession['avatarThumbnail'];
            $request->handlerNickname          = UserService::getByKey('id', $handler, 'nickname');

            Db::commit();

            return Result::success([$request->toArray(), $chatSession]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();

            return Result::unknown($e->getMessage());
        }
    }

    /**
     * 拒绝入群申请.
     *
     * @param int    $id      请求ID
     * @param int    $handler 处理人ID
     * @param string $reason  拒绝原因
     *
     * @return Result
     */
    public function reject(int $id, int $handler, ?string $reason): Result
    {
        // 如果剔除空格后长度为零，则直接置空
        if ($reason && StrUtils::isEmpty($reason)) {
            $reason = null;
        }

        // 如果附加消息长度超出
        if ($reason && StrUtils::length($reason) > self::REASON_MAX_LENGTH) {
            return Result::create(self::CODE_REASON_LONG, self::MSG[self::CODE_REASON_LONG]);
        }

        $request = ChatRequestModel::join('chat_member', 'chat_request.chatroom_id = chat_member.chatroom_id')
            ->join('user_info requester', 'chat_request.requester_id = requester.user_id')
            ->join('chatroom', 'chatroom.id = chat_request.chatroom_id')
            ->where([
                'chat_request.id'     => $id,
                'chat_member.user_id' => $handler,
            ])
            ->where(function ($query) {
                $query->whereOr([
                    ['chat_member.role', '=', ChatMemberModel::ROLE_HOST],
                    ['chat_member.role', '=', ChatMemberModel::ROLE_MANAGE],
                ]);
            })
            ->field([
                'requester.nickname AS requesterNickname',
                'requester.avatar AS requesterAvatarThumbnail',
                'chatroom.name AS chatroomName',
                'chatroom.avatar AS chatroomAvatar',
                'chat_request.*',
            ])
            ->find();

        if (!$request) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        // 已被处理
        if ($request->handler_id) {
            return Result::create(self::CODE_REQUEST_HANDLED, self::MSG[self::CODE_REQUEST_HANDLED]);
        }

        $readedList = array_filter($request->readed_list, function ($o) use ($request) {
            return $o !== $request->requester_id;
        });

        // 如果自己还未读
        if (!in_array($handler, $request->readed_list)) {
            $readedList[] = $handler;
        }

        $request->readed_list   = array_values($readedList);
        $request->reject_reason = $reason;
        $request->status        = ChatRequestModel::STATUS_REJECT;
        $request->handler_id    = $handler;
        $request->update_time   = time() * 1000;
        $request->save();

        ChatSessionService::showChatroomNotice($request->chatroom_id, $request->requester_id);

        $storage = Storage::create();

        $request->requesterAvatarThumbnail = $storage->getThumbnailUrl($request->requesterAvatarThumbnail);
        $request->chatroomAvatarThumbnail  = $storage->getThumbnailUrl($request->chatroomAvatar);
        $request->chatroomAvatar           = $storage->getUrl($request->chatroomAvatar);
        $request->handlerNickname          = UserService::getByKey('id', $handler, 'nickname');

        return Result::success($request);
    }

    /**
     * 已读所有入群请求
     *
     * @return Result
     */
    public function readed(): Result
    {
        $userId = UserService::getId();
        ChatRequestModel::whereRaw("!JSON_CONTAINS(readed_list, JSON_ARRAY({$userId}))")
            ->update([
                'readed_list' => Db::raw("JSON_ARRAY_APPEND(readed_list, '$', {$userId})"),
            ]);

        ChatSessionModel::update(['unread' => 0], [
            'user_id' => $userId,
            'type'    => ChatSessionModel::TYPE_CHATROOM_NOTICE,
        ]);

        return Result::success();
    }

    /**
     * 通过请求ID获取我收到的入群请求
     *
     * @param int $id
     *
     * @return Result
     */
    public function getReceiveRequestById(int $id): Result
    {
        $userId = UserService::getId();

        $request = ChatRequestModel::join('chat_member', 'chat_request.chatroom_id = chat_member.chatroom_id')
            ->join('user_info requester', 'chat_request.requester_id = requester.user_id')
            ->leftJoin('user_info handler', 'chat_request.handler_id = handler.user_id')
            ->join('chatroom', 'chatroom.id = chat_request.chatroom_id')
            ->where([
                'chat_request.id'     => $id,
                'chat_member.user_id' => $userId,
            ])
            ->where(function ($query) {
                $query->whereOr([
                    ['chat_member.role', '=', ChatMemberModel::ROLE_HOST],
                    ['chat_member.role', '=', ChatMemberModel::ROLE_MANAGE],
                ]);
            })
            ->field([
                'requester.nickname AS requesterNickname',
                'requester.avatar AS requesterAvatarThumbnail',
                'handler.nickname AS handlerNickname',
                'chatroom.name AS chatroomName',
                'chat_request.*',
            ])
            ->find();

        if (!$request) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        $storage                           = Storage::create();
        $request->requesterAvatarThumbnail = $storage->getThumbnailUrl($request->requesterAvatarThumbnail);

        return Result::success($request);
    }

    /**
     * 获取我收到的入群申请.
     *
     * @return Result
     */
    public function getReceiveRequests(): Result
    {
        $userId  = UserService::getId();
        $storage = Storage::create();

        $data = ChatRequestModel::join('chat_member', 'chat_request.chatroom_id = chat_member.chatroom_id')
            ->join('user_info requester', 'chat_request.requester_id = requester.user_id')
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
                'requester.nickname AS requesterNickname',
                'requester.avatar AS requesterAvatarThumbnail',
                'handler.nickname AS handlerNickname',
                'chatroom.name AS chatroomName',
                'chat_request.*',
            ])
            ->select();

        foreach ($data as $item) {
            $item->requesterAvatarThumbnail = $storage->getThumbnailUrl($item->requesterAvatarThumbnail);
        }

        return Result::success($data);
    }

    /**
     * 通过请求ID获取我发送的入群请求
     *
     * @param int $id
     *
     * @return Result
     */
    public function getSendRequestById(int $id): Result
    {
        $userId = UserService::getId();

        $request = ChatRequestModel::join('user_info requester', 'chat_request.requester_id = requester.user_id')
            ->leftJoin('user_info handler', 'chat_request.handler_id = handler.user_id')
            ->join('chatroom', 'chatroom.id = chat_request.chatroom_id')
            ->where([
                'chat_request.id'           => $id,
                'chat_request.requester_id' => $userId,
            ])
            ->field([
                'requester.nickname AS requesterNickname',
                'handler.nickname AS handlerNickname',
                'chatroom.name AS chatroomName',
                'chatroom.avatar AS chatroomAvatar',
                'chat_request.*',
            ])
            ->find();

        if (!$request) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        $storage = Storage::create();

        $request->chatroomAvatarThumbnail = $storage->getThumbnailUrl($request->chatroomAvatar);
        $request->chatroomAvatar          = $storage->getUrl($request->chatroomAvatar);

        return Result::success($request);
    }

    /**
     * 获取我发送的所有入群申请.
     *
     * @return Result
     */
    public function getSendRequests(): Result
    {
        $userId = UserService::getId();

        $data = ChatRequestModel::join('user_info requester', 'chat_request.requester_id = requester.user_id')
            ->leftJoin('user_info handler', 'chat_request.handler_id = handler.user_id')
            ->join('chatroom', 'chatroom.id = chat_request.chatroom_id')
            ->where('chat_request.requester_id', '=', $userId)
            ->field([
                'requester.nickname AS requesterNickname',
                'handler.nickname AS handlerNickname',
                'chatroom.name AS chatroomName',
                'chatroom.avatar AS chatroomAvatar',
                'chat_request.*',
            ])
            ->select();

        $storage = Storage::create();

        foreach ($data as $item) {
            $item->chatroomAvatarThumbnail = $storage->getThumbnailUrl($item->chatroomAvatar);
            $item->chatroomAvatar          = $storage->getUrl($item->chatroomAvatar);
        }

        return Result::success($data);
    }
}
