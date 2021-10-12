<?php

declare(strict_types=1);

namespace app\service;

use app\core\Result;
use app\core\storage\Storage;
use app\facade\ChatroomService;
use app\facade\UserService;
use app\model\ChatMember as ChatMemberModel;
use app\model\Chatroom as ChatroomModel;
use app\model\FriendRequest as FriendRequestModel;
use app\model\UserInfo as UserInfoModel;
use app\utils\Str as StrUtils;
use think\facade\Db;

class Friend
{
    /** 别名过长 */
    const CODE_ALIAS_LONG = 1;
    /** 附加消息过长 */
    const CODE_REASON_LONG = 2;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_ALIAS_LONG => '好友别名长度不能大于' . ONCHAT_NICKNAME_MAX_LENGTH . '位字符',
        self::CODE_REASON_LONG  => '附加消息长度不能大于' . ONCHAT_REASON_MAX_LENGTH . '位字符'
    ];

    /**
     * 申请添加好友
     *
     * @param integer $requesterId 申请人的UserID
     * @param integer $targetId 被申请人的UserID
     * @param string $reason 申请原因
     * @param string $targetAlias 被申请人的别名
     * @return Result
     */
    public function request(int $requesterId, int $targetId, string $reason = null, string $targetAlias = null): Result
    {
        // 如果两人已经是好友关系，则不允许申请了
        if ($requesterId === $targetId || $this->isFriend($requesterId, $targetId)) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        // 如果剔除空格后长度为零，则直接置空
        if ($reason && StrUtils::isEmpty($reason)) {
            $reason = null;
        }

        // 如果附加消息长度超出
        if ($reason && StrUtils::length($reason) > ONCHAT_REASON_MAX_LENGTH) {
            return Result::create(self::CODE_REASON_LONG, self::MSG[self::CODE_REASON_LONG]);
        }

        // 如果剔除空格后长度为零，则直接置空
        if ($targetAlias && StrUtils::isEmpty($targetAlias)) {
            $targetAlias = null;
        }

        // 如果别名长度超出
        if ($targetAlias && StrUtils::length($targetAlias) > ONCHAT_NICKNAME_MAX_LENGTH) {
            return Result::create(self::CODE_ALIAS_LONG, self::MSG[self::CODE_ALIAS_LONG]);
        }

        $timestamp = time() * 1000;

        $request = FriendRequestModel::where([
            ['requester_id', '=',  $requesterId],
            ['target_id',    '=',  $targetId],
            ['status',       '<>', FriendRequestModel::STATUS_AGREE]
        ])->find();

        // 如果之前已经申请过，但对方没有同意，就把对方的状态设置成等待验证
        if ($request) {
            $request->request_reason = $reason;
            $request->target_alias = $targetAlias;
            $request->requester_readed = true;
            $request->target_readed = false;
            $request->status = FriendRequestModel::STATUS_WAIT;
            $request->update_time = $timestamp;
            $request->save();
        } else {
            $request = FriendRequestModel::create([
                'requester_id'     => $requesterId,
                'target_id'        => $targetId,
                'request_reason'   => $reason,
                'target_alias'     => $targetAlias,
                'requester_readed' => true,
                'create_time'      => $timestamp,
                'update_time'      => $timestamp,
            ]);
        }

        $storage = Storage::create();

        $userInfos = UserInfoModel::where('user_id', 'IN', [$requesterId, $targetId])->field([
            'user_id',
            'avatar',
            'nickname'
        ])->limit(2)->select();

        $avatarThumbnail = null;
        foreach ($userInfos as $userInfo) {
            $avatarThumbnail = $storage->getThumbnailUrl($userInfo->avatar);

            if ($userInfo->user_id === $requesterId) {
                $request->requesterAvatarThumbnail = $avatarThumbnail;
                $request->requesterNickname        = $userInfo->nickname;
            } else {
                $request->targetAvatarThumbnail = $avatarThumbnail;
                $request->targetNickname        = $userInfo->nickname;
            }
        }

        return Result::success($request);
    }

    /**
     * 通过ID获取FriendRequest
     *
     * @param integer $id
     * @return Result
     */
    public function getRequestById(int $id): Result
    {
        $userId = UserService::getId();

        $request = FriendRequestModel::find($id);

        if (!$request) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        // 如果发请求的这个人不是申请人也不是被申请人，则无权获取
        if ($request->requester_id != $userId && $request->target_id !== $userId) {
            return Result::create(Result::CODE_NO_PERMISSION);
        }

        return Result::success($request);
    }

    /**
     * 获取我的收到好友申请
     *
     * @return Result
     */
    public function getReceiveRequests(): Result
    {
        $targetId = UserService::getId();
        $targetNickname = UserService::getByKey('id', $targetId, 'nickname');

        // 找到自己被申请的
        $requests = FriendRequestModel::join('user_info', 'friend_request.requester_id = user_info.user_id')
            ->where('friend_request.target_id', '=', $targetId)
            ->field([
                'friend_request.*',
                'user_info.avatar AS requesterAvatarThumbnail',
                'user_info.nickname AS requesterNickname',
            ])
            ->order('friend_request.update_time', 'DESC')
            ->select();

        $storage = Storage::create();

        foreach ($requests as $item) {
            $item->requesterAvatarThumbnail = $storage->getThumbnailUrl($item->requesterAvatarThumbnail);
            $item->targetNickname           = $targetNickname;
        }

        return Result::success($requests);
    }

    /**
     * 获取我的发起的好友申请
     *
     * @return Result
     */
    public function getSendRequests(): Result
    {
        $requesterId = UserService::getId();
        $requesterNickname = UserService::getByKey('id', $requesterId, 'nickname');

        $requests = FriendRequestModel::join('user_info', 'friend_request.target_id = user_info.user_id')
            ->where('friend_request.requester_id', '=', $requesterId)
            ->field([
                'friend_request.*',
                'user_info.avatar AS targetAvatarThumbnail',
                'user_info.nickname AS targetNickname',
            ])
            ->order('update_time', 'DESC')
            ->select();

        $storage = Storage::create();

        foreach ($requests as $item) {
            $item->targetAvatarThumbnail = $storage->getThumbnailUrl($item->targetAvatarThumbnail);
            $item->requesterNickname     = $requesterNickname;
        }

        return Result::success($requests);
    }

    /**
     * 根据被申请人ID来获取FriendRequest
     *
     * @param integer $targetId
     * @return Result
     */
    public function getRequestByTargetId(int $targetId): Result
    {
        $userId = UserService::getId();

        $request = FriendRequestModel::where([
            'requester_id'   => $userId,
            'target_id'      => $targetId
        ])->find();

        return Result::success($request);
    }

    /**
     * 根据申请人ID来获取FriendRequest
     *
     * @param integer $requesterId
     * @return Result
     */
    public function getRequestByRequesterId(int $requesterId): Result
    {
        $userId = UserService::getId();

        $request = FriendRequestModel::where([
            'requester_id'   => $requesterId,
            'target_id' => $userId
        ])->find();

        return Result::success($request);
    }

    /**
     * 同意好友申请
     *
     * @param integer $requestId 好友申请表的ID
     * @param integer $targetId 被申请人的ID
     * @param string $requesterAlias 申请人的别名
     * @return Result
     */
    public function agree(int $requestId, int $targetId, string $requesterAlias = null): Result
    {
        // 如果剔除空格后长度为零，则直接置空
        if ($requesterAlias && StrUtils::isEmpty($requesterAlias)) {
            $requesterAlias = null;
        }

        // 如果别名长度超出
        if ($requesterAlias && StrUtils::length($requesterAlias) > ONCHAT_NICKNAME_MAX_LENGTH) {
            return Result::create(self::CODE_ALIAS_LONG, self::MSG[self::CODE_ALIAS_LONG]);
        }

        $request = FriendRequestModel::find($requestId);

        if (!$request) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        // 确认被申请人的身份
        if ($request->target_id !== $targetId) {
            return Result::create(Result::CODE_NO_PERMISSION);
        }

        // 启动事务
        Db::startTrans();
        try {
            $request->status           = FriendRequestModel::STATUS_AGREE;
            $request->requester_alias  = $requesterAlias;
            $request->target_readed    = true;
            $request->requester_readed = true;
            $request->update_time      = time() * 1000;
            $request->save();

            // 去找一下有没有自己申请加对方的申请记录
            // 场景：自己同意了对方的申请，但是自己之前也向对方提出好友申请
            // 找到就直接删除吧，省点空间
            FriendRequestModel::where([
                'requester_id' => $targetId,
                'target_id' => $request->requester_id
            ])->delete();

            // 创建一个类型为私聊的聊天室
            $result = ChatroomService::creatChatroom('PRIVATE_CHATROOM', ChatroomModel::TYPE_PRIVATE_CHAT);
            if ($result->isError()) {
                Db::rollback();
                return $result;
            }

            $chatroomId = $result->data['id'];

            $result = ChatroomService::addMember($chatroomId, $request->requester_id, $requesterAlias);
            if ($result->isError()) {
                Db::rollback();
                return $result;
            }

            $result = ChatroomService::addMember($chatroomId, $request->target_id, $request->target_alias);
            if ($result->isError()) {
                Db::rollback();
                return $result;
            }

            Db::commit();

            $userInfo = UserService::getByKey('id', $request->target_id, [
                'nickname',
                'avatar'
            ]);

            $storage = Storage::create();

            return Result::success([
                'friendRequestId'       => $request->id,
                'chatroomId'            => $chatroomId,
                'requesterId'           => $request->requester_id,
                'targetId'              => $request->target_id,
                'targetNickname'        => $userInfo->nickname,
                'targetAvatarThumbnail' => $storage->getThumbnailUrl($userInfo->avatar)
            ]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return Result::unknown($e->getMessage());
        }
    }

    /**
     * 拒绝好友申请
     *
     * @param integer $requestId 好友申请表的ID
     * @param integer $targetId 被申请人的ID
     * @param string $reason 拒绝原因
     * @return Result
     */
    public function reject(int $requestId, int $targetId, string $reason = null): Result
    {
        // 如果剔除空格后长度为零，则直接置空
        if ($reason && StrUtils::isEmpty($reason)) {
            $reason = null;
        }

        // 如果附加消息长度超出
        if ($reason && StrUtils::length($reason) > ONCHAT_REASON_MAX_LENGTH) {
            return Result::create(self::CODE_REASON_LONG, self::MSG[self::CODE_REASON_LONG]);
        }

        $request = FriendRequestModel::where([
            'id' => $requestId,
            'status' => FriendRequestModel::STATUS_WAIT
        ])->find();

        if (!$request) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        // 确认被申请人的身份
        if ($request->target_id !== $targetId) {
            return Result::create(Result::CODE_NO_PERMISSION);
        }

        // 启动事务
        Db::startTrans();
        try {
            $request->status           = FriendRequestModel::STATUS_REJECT;
            $request->target_readed    = true;
            $request->requester_readed = false;
            $request->reject_reason    = $reason;
            $request->update_time      = time() * 1000;
            $request->save();

            // 去找一下有没有自己申请加对方的申请记录
            // 场景：自己拒绝了对方的申请，但是自己之前也向对方提出好友申请
            // 找到就直接删除吧，省点空间
            FriendRequestModel::where([
                'requester_id' => $targetId,
                'target_id' => $request->requester_id
            ])->delete();

            $storage = Storage::create();

            $userInfos = UserInfoModel::where('user_id', 'IN', [$request->requester_id, $targetId])->field([
                'user_id',
                'avatar',
                'nickname'
            ])->limit(2)->select();

            $avatarThumbnail = null;
            foreach ($userInfos as $userInfo) {
                $avatarThumbnail = $storage->getThumbnailUrl($userInfo->avatar);

                if ($userInfo->user_id === $targetId) {
                    $request->targetNickname        = $userInfo->nickname;
                    $request->targetAvatarThumbnail = $avatarThumbnail;
                } else {
                    $request->requesterNickname        = $userInfo->nickname;
                    $request->requesterAvatarThumbnail = $avatarThumbnail;
                }
            }

            Db::commit();
            return Result::success($request);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return Result::unknown($e->getMessage());
        }
    }

    /**
     * 判断二人是否为好友关系
     * 如果是好友关系，则返回私聊房间号；否则返回零
     *
     * @param integer $requesterId
     * @param integer $targetId
     * @return integer
     */
    public function isFriend(int $requesterId, int $targetId): int
    {
        if ($requesterId === $targetId) {
            return 0; // TODO 找到自己的单聊聊天室
        }

        // 找到二人的共同聊天室，且聊天室类型为私聊
        $chatroom = ChatroomModel::where('type', '=', ChatroomModel::TYPE_PRIVATE_CHAT)->where('id', 'IN', function ($query) use ($requesterId, $targetId) {
            // 找到self跟target共同聊天室的ID
            $query->table('chat_member')->where('user_id', '=', $requesterId)->where('chatroom_id', 'IN', function ($query) use ($targetId) {
                // 找到target所加入的所有聊天室的ID
                $query->table('chat_member')->where('user_id', '=', $targetId)->field('chatroom_id');
            })->field('chatroom_id');
        })->find();

        return empty($chatroom) ? 0 : $chatroom->id;
    }

    /**
     * 设置好友别名
     *
     * @param integer $chatroomId 私聊房间号
     * @param string $alias 好友别名
     * @return Result
     */
    public function setFriendAlias(int $chatroomId, ?string $alias): Result
    {
        $userId = UserService::getId();

        // 如果有传入别名
        if ($alias && !StrUtils::isEmpty($alias)) {
            $alias = trim($alias);
            // 如果别名长度超出
            if (StrUtils::length($alias) > ONCHAT_NICKNAME_MAX_LENGTH) {
                return Result::create(self::CODE_ALIAS_LONG, self::MSG[self::CODE_ALIAS_LONG]);
            }
        } else {
            $alias = null;
        }

        $chatroom = ChatroomModel::find($chatroomId); // 找到这个聊天室

        // 如果没有这个聊天室或者这个聊天室不是私聊的
        if (!$chatroom || $chatroom->type != ChatroomModel::TYPE_PRIVATE_CHAT) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        $chatMember = ChatMemberModel::where([
            ['chatroom_id', '=', $chatroomId],
            ['user_id', '<>', $userId]
        ])->find();

        if (!$chatMember) {
            return Result::unknown('该私聊聊天室没有没有其他成员');
        }

        if (!$alias) {
            $alias = UserService::getUsernameById($chatMember->user_id);
        }

        $chatMember->nickname = $alias;
        $chatMember->save();

        return Result::success($alias);
    }

    /**
     * 已读收到的好友请求
     * @param integer $id
     *
     * @return Result
     */
    public function readedReceiveRequest(int $id): Result
    {
        $userId = UserService::getId();

        FriendRequestModel::where([
            'id'            => $id,
            'target_id'     => $userId,
            'target_readed' => false
        ])->update([
            'target_readed' => true
        ]);

        return Result::success();
    }

    /**
     * 已读发送的好友请求
     * @param integer $id
     *
     * @return Result
     */
    public function readedSendRequest(int $id): Result
    {
        $userId = UserService::getId();

        FriendRequestModel::where([
            'id'               => $id,
            'requester_id'     => $userId,
            'requester_readed' => false
        ])->update([
            'requester_readed' => true
        ]);

        return Result::success();
    }
}
