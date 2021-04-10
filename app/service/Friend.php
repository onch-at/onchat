<?php

declare(strict_types=1);

namespace app\service;

use app\core\Result;
use app\core\storage\Storage;
use app\facade\ChatroomService;
use app\facade\UserService;
use app\facade\UserTable;
use app\model\ChatMember as ChatMemberModel;
use app\model\Chatroom as ChatroomModel;
use app\model\FriendRequest as FriendRequestModel;
use app\model\UserInfo as UserInfoModel;
use app\util\Str as StrUtil;
use think\facade\Db;

class Friend
{
    /** 别名最大长度 */
    const ALIAS_MAX_LENGTH = 30;
    /** 附加消息最大长度 */
    const REASON_MAX_LENGTH = 50;

    /** 别名过长 */
    const CODE_ALIAS_LONG = 1;
    /** 附加消息过长 */
    const CODE_REASON_LONG = 2;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_ALIAS_LONG => '好友别名长度不能大于' . self::ALIAS_MAX_LENGTH . '位字符',
        self::CODE_REASON_LONG  => '附加消息长度不能大于' . self::REASON_MAX_LENGTH . '位字符'
    ];

    /**
     * 申请添加好友
     *
     * @param integer $selfId 申请人的UserID
     * @param integer $targetId 被申请人的UserID
     * @param string $reason 申请原因
     * @param string $targetAlias 被申请人的别名
     * @return Result
     */
    public function request(int $selfId, int $targetId, ?string $reason = null, ?string $targetAlias = null): Result
    {
        // 如果两人已经是好友关系，则不允许申请了
        if ($selfId == $targetId || $this->isFriend($selfId, $targetId)) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 如果剔除空格后长度为零，则直接置空
        if ($reason && mb_strlen(StrUtil::trimAll($reason), 'utf-8') == 0) {
            $reason = null;
        }

        // 如果附加消息长度超出
        if ($reason && mb_strlen($reason, 'utf-8') > self::REASON_MAX_LENGTH) {
            return new Result(self::CODE_REASON_LONG, self::MSG[self::CODE_REASON_LONG]);
        }

        // 如果剔除空格后长度为零，则直接置空
        if ($targetAlias && mb_strlen(StrUtil::trimAll($targetAlias), 'utf-8') == 0) {
            $targetAlias = null;
        }

        // 如果别名长度超出
        if ($targetAlias && mb_strlen($targetAlias, 'utf-8') > self::ALIAS_MAX_LENGTH) {
            return new Result(self::CODE_ALIAS_LONG, self::MSG[self::CODE_ALIAS_LONG]);
        }

        $storage = Storage::getInstance();

        $selfAvatarThumbnail = null;
        $targetAvatarThumbnail = null;

        $userInfos = UserInfoModel::where('user_id', 'IN', [$selfId, $targetId])->field([
            'user_id',
            'avatar',
            'nickname'
        ])->limit(2)->select();

        $url = null;
        $selfUsername = null;
        $targetUsername = null;
        foreach ($userInfos as $userInfo) {
            $url = $storage->getThumbnailImageUrl($userInfo->avatar);

            switch ($userInfo['user_id']) {
                case $selfId:
                    $selfAvatarThumbnail = $url;
                    $selfUsername = $userInfo->nickname;
                    break;

                case $targetId:
                    $targetAvatarThumbnail = $url;
                    $targetUsername = $userInfo->nickname;
                    break;
            }
        }

        $timestamp = time() * 1000;

        $friendRequest = FriendRequestModel::where([
            ['self_id', '=', $selfId],
            ['target_id', '=', $targetId],
            ['target_status', '<>', FriendRequestModel::STATUS_AGREE]
        ])->find();

        // 如果之前已经申请过，但对方没有同意，就把对方的状态设置成等待验证
        if ($friendRequest) {
            $friendRequest->request_reason = $reason;
            $friendRequest->target_alias = $targetAlias;
            // 将双方的状态都设置为等待验证
            $friendRequest->self_status = FriendRequestModel::STATUS_WAIT;
            $friendRequest->target_status = FriendRequestModel::STATUS_WAIT;

            $friendRequest->update_time = $timestamp;
            $friendRequest->save();
        } else {
            $friendRequest = FriendRequestModel::create([
                'self_id'        => $selfId,
                'target_id'      => $targetId,
                'request_reason' => $reason,
                'target_alias'   => $targetAlias,
                'create_time'    => $timestamp,
                'update_time'    => $timestamp,
            ]);
        }

        $friendRequest = $friendRequest->toArray();

        $friendRequest['selfAvatarThumbnail'] = $selfAvatarThumbnail;
        $friendRequest['selfUsername'] = $selfUsername;
        $friendRequest['targetAvatarThumbnail'] = $targetAvatarThumbnail;
        $friendRequest['targetUsername'] = $targetUsername;

        return Result::success($friendRequest);
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

        $friendRequest = FriendRequestModel::find($id);

        if (!$friendRequest) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 如果发请求的这个人不是申请人也不是被申请人，则无权获取
        if ($friendRequest->self_id != $userId && $friendRequest->target_id != $userId) {
            return new Result(Result::CODE_ERROR_NO_PERMISSION);
        }

        return Result::success($friendRequest->toArray());
    }

    /**
     * 获取我的收到好友申请
     *
     * @return Result
     */
    public function getReceiveRequests(): Result
    {
        $userId = UserService::getId();
        $username = UserService::getUsername();

        // 找到自己被申请的
        $friendRequests = FriendRequestModel::join('user', 'friend_request.self_id = user.id')
            ->join('user_info', 'friend_request.self_id = user_info.user_id')
            ->where([
                'friend_request.target_id' => $userId,
                'friend_request.target_status' => FriendRequestModel::STATUS_WAIT
            ])
            ->field([
                'friend_request.id',
                'friend_request.self_id',
                'friend_request.target_id',
                'friend_request.request_reason',
                'friend_request.reject_reason',
                'friend_request.self_status',
                'friend_request.target_status',
                'friend_request.create_time',
                'friend_request.update_time',
                'user_info.avatar AS selfAvatarThumbnail',
                'user.username AS selfUsername',
            ])
            ->order('friend_request.update_time', 'DESC')
            ->select()
            ->toArray();

        $storage = Storage::getInstance();

        foreach ($friendRequests as $key => $value) {
            $friendRequests[$key]['selfAvatarThumbnail'] = $storage->getThumbnailImageUrl($value['selfAvatarThumbnail']);
            $friendRequests[$key]['targetUsername'] = $username;
        }

        return Result::success($friendRequests);
    }

    /**
     * 获取我的发起的好友申请（不包含已经同意的）
     *
     * @return Result
     */
    public function getSendRequests(): Result
    {
        $userId = UserService::getId();
        $username = UserService::getUsername();

        $friendRequests = FriendRequestModel::join('user', 'friend_request.target_id = user.id')
            ->join('user_info', 'friend_request.target_id = user_info.user_id')
            ->where('friend_request.self_id', '=', $userId)
            ->where(function ($query) {
                $query->whereOr([
                    ['friend_request.self_status', '=', FriendRequestModel::STATUS_WAIT],
                    ['friend_request.self_status', '=', FriendRequestModel::STATUS_REJECT]
                ]);
            })
            ->field([
                'friend_request.id',
                'friend_request.self_id',
                'friend_request.target_id',
                'friend_request.request_reason',
                'friend_request.reject_reason',
                'friend_request.self_status',
                'friend_request.target_status',
                'friend_request.create_time',
                'friend_request.update_time',
                'user_info.avatar AS targetAvatarThumbnail',
                'user.username AS targetUsername',
            ])
            ->order('update_time', 'DESC')
            ->select()
            ->toArray();

        $storage = Storage::getInstance();

        foreach ($friendRequests as $key => $value) {
            $friendRequests[$key]['targetAvatarThumbnail'] = $storage->getThumbnailImageUrl($value['targetAvatarThumbnail']);
            $friendRequests[$key]['selfUsername'] = $username;
        }

        return Result::success($friendRequests);
    }

    /**
     * 根据被申请人UID来获取FriendRequest
     *
     * @param integer $targetId
     * @return Result
     */
    public function getRequestByTargetId(int $targetId): Result
    {
        $userId = UserService::getId();

        $friendRequest = FriendRequestModel::where([
            'self_id'   => $userId,
            'target_id' => $targetId
        ])->find();

        if (!$friendRequest) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        return Result::success($friendRequest->toArray());
    }

    /**
     * 根据申请人UID来获取FriendRequest
     *
     * @param integer $selfId
     * @return Result
     */
    public function getRequestBySelfId(int $selfId): Result
    {
        $userId = UserService::getId();

        $friendRequest = FriendRequestModel::where([
            'self_id'   => $selfId,
            'target_id' => $userId
        ])->find();

        if (!$friendRequest) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        return Result::success($friendRequest->toArray());
    }

    /**
     * 同意好友申请
     *
     * @param integer $requestId 好友申请表的ID
     * @param integer $targetId 被申请人的ID
     * @param string $selfAlias 申请人的别名
     * @return Result
     */
    public function agree(int $requestId, int $targetId, string $selfAlias = null): Result
    {
        // 如果剔除空格后长度为零，则直接置空
        $selfAlias && mb_strlen(StrUtil::trimAll($selfAlias), 'utf-8') == 0 && ($selfAlias = null);

        // 如果别名长度超出
        if ($selfAlias && mb_strlen($selfAlias, 'utf-8') > self::ALIAS_MAX_LENGTH) {
            return new Result(self::CODE_ALIAS_LONG, self::MSG[self::CODE_ALIAS_LONG]);
        }

        $friendRequest = FriendRequestModel::find($requestId);

        if (!$friendRequest) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 确认被申请人的身份
        if ($friendRequest->target_id != $targetId) {
            return new Result(Result::CODE_ERROR_NO_PERMISSION);
        }

        // 启动事务
        Db::startTrans();
        try {
            // $friendRequest->self_status = FriendRequestModel::STATUS_AGREE;
            // $friendRequest->target_status = FriendRequestModel::STATUS_AGREE;
            // $friendRequest->self_alias = $selfAlias;
            // $friendRequest->update_time = $timestamp;
            // $friendRequest->save();
            $friendRequest->delete(); // 同意就直接删除吧

            // 去找一下有没有自己申请加对方的申请记录
            // 场景：自己同意了对方的申请，但是自己之前也向对方提出好友申请
            // 找到就直接删除吧，省点空间
            FriendRequestModel::where([
                'self_id' => $targetId,
                'target_id' => $friendRequest->self_id
            ])->delete();

            // 创建一个类型为私聊的聊天室
            $result = ChatroomService::creatChatroom('PRIVATE_CHATROOM', ChatroomModel::TYPE_PRIVATE_CHAT);
            if ($result->code !== Result::CODE_SUCCESS) {
                Db::rollback();
                return $result;
            }

            $chatroomId = $result->data['id'];

            $result = ChatroomService::addMember($chatroomId, $friendRequest->self_id, $selfAlias);
            if ($result->code !== Result::CODE_SUCCESS) {
                Db::rollback();
                return $result;
            }

            $result = ChatroomService::addMember($chatroomId, $friendRequest->target_id, $friendRequest->target_alias);
            if ($result->code !== Result::CODE_SUCCESS) {
                Db::rollback();
                return $result;
            }

            Db::commit();

            $userInfo = UserService::getInfoByKey('id', $friendRequest->target_id, [
                'username',
                'avatar'
            ]);

            $storage = Storage::getInstance();

            return Result::success([
                'friendRequestId'       => $friendRequest->id,
                'chatroomId'            => $chatroomId,
                'selfId'                => $friendRequest->self_id,
                'targetId'              => $friendRequest->target_id,
                'targetUsername'        => $userInfo['username'],
                'targetAvatarThumbnail' => $storage->getThumbnailImageUrl($userInfo['avatar'])
            ]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
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
        if ($reason && mb_strlen(StrUtil::trimAll($reason), 'utf-8') == 0) {
            $reason = null;
        }

        // 如果附加消息长度超出
        if ($reason && mb_strlen($reason, 'utf-8') > self::REASON_MAX_LENGTH) {
            return new Result(self::CODE_REASON_LONG, self::MSG[self::CODE_REASON_LONG]);
        }

        $friendRequest = FriendRequestModel::find($requestId);

        if (!$friendRequest) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 确认被申请人的身份
        if ($friendRequest->target_id != $targetId) {
            return new Result(Result::CODE_ERROR_NO_PERMISSION);
        }

        // 启动事务
        Db::startTrans();
        try {
            $friendRequest->self_status = FriendRequestModel::STATUS_REJECT;
            $friendRequest->target_status = FriendRequestModel::STATUS_REJECT;
            $friendRequest->reject_reason = $reason;
            $friendRequest->update_time = time() * 1000;
            $friendRequest->save();

            // 去找一下有没有自己申请加对方的申请记录
            // 场景：自己拒绝了对方的申请，但是自己之前也向对方提出好友申请
            // 找到就直接删除吧，省点空间
            FriendRequestModel::where([
                'self_id' => $targetId,
                'target_id' => $friendRequest->self_id
            ])->delete();

            $storage = Storage::getInstance();
            $object = UserService::getInfoByKey('id', $targetId, 'avatar')['avatar'];

            $data = $friendRequest->toArray();
            $data['selfUsername'] = UserService::getUsernameById($friendRequest->self_id);
            $data['targetUsername'] = UserTable::getByUserId($targetId, 'username');
            $data['targetAvatarThumbnail'] = $storage->getThumbnailImageUrl($object);
            Db::commit();

            return Result::success($data);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * 判断二人是否为好友关系
     * 如果是好友关系，则返回私聊房间号；否则返回零
     *
     * @param integer $selfId
     * @param integer $targetId
     * @return integer
     */
    public function isFriend(int $selfId, int $targetId): int
    {
        if ($selfId == $targetId) {
            return 0; // TODO 找到自己的单聊聊天室
        }

        // 找到二人的共同聊天室，且聊天室类型为私聊
        $chatroom = ChatroomModel::where('type', '=', ChatroomModel::TYPE_PRIVATE_CHAT)->where('id', 'IN', function ($query) use ($selfId, $targetId) {
            // 找到self跟target共同聊天室的ID
            $query->table('chat_member')->where('user_id', '=', $selfId)->where('chatroom_id', 'IN', function ($query) use ($targetId) {
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
    public function setFriendAlias(int $chatroomId, string $alias): Result
    {
        $userId = UserService::getId();

        // 如果有传入别名
        if (mb_strlen(StrUtil::trimAll($alias), 'utf-8') != 0) {
            $alias = trim($alias);
            // 如果别名长度超出
            if (mb_strlen($alias, 'utf-8') > self::ALIAS_MAX_LENGTH) {
                return new Result(self::CODE_ALIAS_LONG, self::MSG[self::CODE_ALIAS_LONG]);
            }
        } else {
            $alias = null;
        }

        $chatroom = ChatroomModel::find($chatroomId); // 找到这个聊天室

        // 如果没有这个聊天室或者这个聊天室不是私聊的
        if (!$chatroom || $chatroom->type != ChatroomModel::TYPE_PRIVATE_CHAT) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        $chatMember = ChatMemberModel::where([
            ['chatroom_id', '=', $chatroomId],
            ['user_id', '<>', $userId]
        ])->find();

        if (!$chatMember) {
            return new Result(Result::CODE_ERROR_UNKNOWN, '该私聊聊天室没有没有其他成员');
        }

        if (!$alias) {
            $alias = UserService::getUsernameById($chatMember->user_id);
        }

        $chatMember->nickname = $alias;
        $chatMember->save();

        return Result::success($alias);
    }
}
