<?php

declare(strict_types=1);

namespace app\core\service;

use app\core\Result;
use think\facade\Db;
use app\model\User as UserModel;
use app\core\util\Arr as ArrUtil;
use app\core\util\Sql as SqlUtil;
use app\core\util\Str as StrUtil;
use app\core\oss\Client as OssClient;
use app\model\Chatroom as ChatroomModel;
use app\model\UserInfo as UserInfoModel;
use app\model\ChatMember as ChatMemberModel;
use app\model\FriendRequest as FriendRequestModel;

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
     * @param string $selfUsername 申请人的用户名
     * @param string $requestReason 申请原因
     * @param string $targetAlias 被申请人的别名
     * @return Result
     */
    public static function request(int $selfId, int $targetId, string $selfUsername, string $requestReason = null, string $targetAlias = null): Result
    {
        // 如果剔除空格后长度为零，则直接置空
        $requestReason && mb_strlen(StrUtil::trimAll($requestReason), 'utf-8') == 0 && ($requestReason = null);

        // 如果附加消息长度超出
        if ($requestReason && mb_strlen($requestReason, 'utf-8') > self::REASON_MAX_LENGTH) {
            return new Result(self::CODE_REASON_LONG, self::MSG[self::CODE_REASON_LONG]);
        }

        // 如果剔除空格后长度为零，则直接置空
        $targetAlias && mb_strlen(StrUtil::trimAll($targetAlias), 'utf-8') == 0 && ($targetAlias = null);

        // 如果别名长度超出
        if ($targetAlias && mb_strlen($targetAlias, 'utf-8') > self::ALIAS_MAX_LENGTH) {
            return new Result(self::CODE_ALIAS_LONG, self::MSG[self::CODE_ALIAS_LONG]);
        }

        // 如果两人已经是好友关系，则不允许申请了
        if ($selfId == $targetId || self::isFriend($selfId, $targetId)) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        $query = FriendRequestModel::where([
            'self_id'        => $selfId,
            'target_id'      => $targetId
        ]);

        $ossClient = OssClient::getInstance();
        $stylename = OssClient::getThumbnailImgStylename();

        $selfAvatarThumbnail = null;
        $targetAvatarThumbnail = null;

        $userInfos = UserInfoModel::where('user_id', 'IN', [$selfId, $targetId])->field([
            'user_id',
            'avatar'
        ])->select()->toArray();

        $signUrl = null;
        foreach ($userInfos as $userInfo) {
            $signUrl = $ossClient->signImageUrl($userInfo['avatar'],  $stylename);

            switch ($userInfo['user_id']) {
                case $selfId:
                    $selfAvatarThumbnail = $signUrl;
                    break;

                case $targetId:
                    $targetAvatarThumbnail = $signUrl;
                    break;
            }
        }

        $timestamp = time() * 1000;
        $friendRequest = $query->where('target_status', '<>', FriendRequestModel::STATUS_AGREE)->find();
        // 如果之前已经申请过，但对方没有同意，就把对方的状态设置成等待验证
        if (!empty($friendRequest)) {
            $friendRequest->request_reason = $requestReason;
            $friendRequest->target_alias = $targetAlias;
            // 将双方的状态都设置为等待验证
            $friendRequest->self_status = FriendRequestModel::STATUS_WAIT;
            $friendRequest->target_status = FriendRequestModel::STATUS_WAIT;

            $friendRequest->update_time = $timestamp;
            $friendRequest->save();

            $friendRequest = $friendRequest->toArray();

            $friendRequest['selfAvatarThumbnail'] = $selfAvatarThumbnail;
            $friendRequest['selfUsername'] = $selfUsername;
            $friendRequest['targetAvatarThumbnail'] = $targetAvatarThumbnail;
            $friendRequest['targetUsername'] = User::getUsernameById($targetId);

            return Result::success(ArrUtil::keyToCamel($friendRequest));
        }

        $friendRequest = FriendRequestModel::create([
            'self_id'        => $selfId,
            'target_id'      => $targetId,
            'request_reason' => $requestReason,
            'target_alias'   => $targetAlias,
            'create_time'    => $timestamp,
            'update_time'    => $timestamp,
        ]);

        $friendRequest = $friendRequest->toArray();

        $friendRequest['selfAvatarThumbnail'] = $selfAvatarThumbnail;
        $friendRequest['selfUsername'] = $selfUsername;
        $friendRequest['targetAvatarThumbnail'] = $targetAvatarThumbnail;
        $friendRequest['targetUsername'] = User::getUsernameById($targetId);

        return Result::success(ArrUtil::keyToCamel($friendRequest));
    }

    /**
     * 通过ID获取FriendRequest
     *
     * @param integer $id
     * @return Result
     */
    public static function getFriendRequestById(int $id): Result
    {
        $userId = User::getId();
        if (!$userId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        $friendRequest = FriendRequestModel::find($id);

        if (!$friendRequest) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 如果发请求的这个人不是申请人也不是被申请人，则无权获取
        if ($friendRequest->self_id != $userId && $friendRequest->target_id != $userId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        return Result::success(ArrUtil::keyToCamel($friendRequest->toArray()));
    }

    /**
     * 获取我的收到好友申请
     *
     * @return Result
     */
    public static function getReceiveFriendRequests(): Result
    {
        $userId = User::getId();
        $username = User::getUsername();

        if (!$userId || !$username) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        // 找到自己被申请的
        $friendRequests = FriendRequestModel::where([
            'friend_request.target_id' => $userId,
            'friend_request.target_status' => FriendRequestModel::STATUS_WAIT
        ])->join('user', 'friend_request.self_id = user.id')
            ->join('user_info', 'friend_request.self_id = user_info.user_id')
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
                'user_info.avatar as selfAvatarThumbnail',
                'user.username as selfUsername',
            ])->order('friend_request.update_time', 'DESC')->select()->toArray();

        $ossClient = OssClient::getInstance();
        $stylename = OssClient::getThumbnailImgStylename();

        foreach ($friendRequests as $key => $value) {
            $friendRequests[$key]['selfAvatarThumbnail'] = $ossClient->signImageUrl($value['selfAvatarThumbnail'], $stylename);
            $friendRequests[$key]['targetUsername'] = $username;
        }

        return Result::success(ArrUtil::keyToCamel($friendRequests));
    }

    /**
     * 获取我的发起的好友申请（不包含已经同意的）
     *
     * @return Result
     */
    public static function getSendFriendRequests(): Result
    {
        $userId = User::getId();
        $username = User::getUsername();

        if (!$userId || !$username) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        $friendRequests = FriendRequestModel::where('friend_request.self_id', '=', $userId)->where(function ($query) {
            $query->whereOr([
                ['friend_request.self_status', '=', FriendRequestModel::STATUS_WAIT],
                ['friend_request.self_status', '=', FriendRequestModel::STATUS_REJECT]
            ]);
        })->join('user', 'friend_request.target_id = user.id')
            ->join('user_info', 'friend_request.target_id = user_info.user_id')
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
                'user_info.avatar as targetAvatarThumbnail',
                'user.username as targetUsername',
            ])->order('update_time', 'DESC')->select()->toArray();

        $ossClient = OssClient::getInstance();
        $stylename = OssClient::getThumbnailImgStylename();

        foreach ($friendRequests as $key => $value) {
            $friendRequests[$key]['targetAvatarThumbnail'] = $ossClient->signImageUrl($value['targetAvatarThumbnail'], $stylename);
            $friendRequests[$key]['selfUsername'] = $username;
        }

        return Result::success(ArrUtil::keyToCamel($friendRequests));
    }

    /**
     * 根据被申请人UID来获取FriendRequest
     *
     * @param integer $targetId
     * @return Result
     */
    public static function getFriendRequestByTargetId(int $targetId): Result
    {
        $userId = User::getId();
        if (!$userId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        $friendRequest = FriendRequestModel::where([
            'self_id'   => $userId,
            'target_id' => $targetId
        ])->find();

        if (!$friendRequest) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        return Result::success(ArrUtil::keyToCamel($friendRequest->toArray()));
    }

    /**
     * 根据申请人UID来获取FriendRequest
     *
     * @param integer $selfId
     * @return Result
     */
    public static function getFriendRequestBySelfId(int $selfId): Result
    {
        $userId = User::getId();
        if (!$userId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        $friendRequest = FriendRequestModel::where([
            'self_id'   => $selfId,
            'target_id' => $userId
        ])->find();

        if (!$friendRequest) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        return Result::success(ArrUtil::keyToCamel($friendRequest->toArray()));
    }

    /**
     * 同意好友申请
     *
     * @param integer $friendRequestId 好友申请表的ID
     * @param integer $targetId 被申请人的ID
     * @param string $selfAlias 申请人的别名
     * @return Result
     */
    public static function agreeRequest(int $friendRequestId, int $targetId, string $selfAlias = null): Result
    {
        // 如果剔除空格后长度为零，则直接置空
        $selfAlias && mb_strlen(StrUtil::trimAll($selfAlias), 'utf-8') == 0 && ($selfAlias = null);

        // 如果别名长度超出
        if ($selfAlias && mb_strlen($selfAlias, 'utf-8') > self::ALIAS_MAX_LENGTH) {
            return new Result(self::CODE_ALIAS_LONG, self::MSG[self::CODE_ALIAS_LONG]);
        }

        $friendRequest = FriendRequestModel::find($friendRequestId);

        if (!$friendRequest) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 确认被申请人的身份
        if ($friendRequest->target_id != $targetId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
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
            $result = Chatroom::creatChatroom('PRIVATE_CHATROOM', ChatroomModel::TYPE_PRIVATE_CHAT);
            if ($result->code != Result::CODE_SUCCESS) {
                return $result;
            }

            $chatroomId = $result->data;

            $result = Chatroom::addChatMember($chatroomId, $friendRequest->self_id, $selfAlias);
            if ($result->code != Result::CODE_SUCCESS) {
                return $result;
            }

            $result = Chatroom::addChatMember($chatroomId, $friendRequest->target_id, $friendRequest->target_alias);
            if ($result->code != Result::CODE_SUCCESS) {
                return $result;
            }

            Db::commit();

            $userInfo = User::getInfoByKey('id', $friendRequest->target_id, [
                'username',
                'avatar'
            ]);

            $ossClient = OssClient::getInstance();

            return Result::success([
                'friendRequestId'       => $friendRequest->id,
                'chatroomId'            => $chatroomId,
                'selfId'                => $friendRequest->self_id,
                'targetId'              => $friendRequest->target_id,
                'targetUsername'        => $userInfo['username'],
                'targetAvatarThumbnail' => $ossClient->signImageUrl($userInfo['avatar'], OssClient::getThumbnailImgStylename())
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
     * @param integer $friendRequestId 好友申请表的ID
     * @param integer $targetId 被申请人的ID
     * @param string $targetUsername 被申请人的用户名
     * @param string $rejectReason 拒绝原因
     * @return Result
     */
    public static function rejectRequest(int $friendRequestId, int $targetId, string $targetUsername, string $rejectReason = null): Result
    {
        // 如果剔除空格后长度为零，则直接置空
        $rejectReason && mb_strlen(StrUtil::trimAll($rejectReason), 'utf-8') == 0 && ($rejectReason = null);

        // 如果附加消息长度超出
        if ($rejectReason && mb_strlen($rejectReason, 'utf-8') > self::REASON_MAX_LENGTH) {
            return new Result(self::CODE_REASON_LONG, self::MSG[self::CODE_REASON_LONG]);
        }

        $friendRequest = FriendRequestModel::find($friendRequestId);

        if (!$friendRequest) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 确认被申请人的身份
        if ($friendRequest->target_id != $targetId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        // 启动事务
        Db::startTrans();
        try {
            $friendRequest->self_status = FriendRequestModel::STATUS_REJECT;
            $friendRequest->target_status = FriendRequestModel::STATUS_REJECT;
            $friendRequest->reject_reason = $rejectReason;
            $friendRequest->update_time = time() * 1000;
            $friendRequest->save();

            // 去找一下有没有自己申请加对方的申请记录
            // 场景：自己拒绝了对方的申请，但是自己之前也向对方提出好友申请
            // 找到就直接删除吧，省点空间
            FriendRequestModel::where([
                'self_id' => $targetId,
                'target_id' => $friendRequest->self_id
            ])->delete();

            $ossClient = OssClient::getInstance();
            $object = User::getInfoByKey('id', $targetId, 'avatar')['avatar'];

            $data = $friendRequest->toArray();
            $data['selfUsername'] = User::getUsernameById($friendRequest->self_id);
            $data['targetUsername'] = $targetUsername;
            $data['targetAvatarThumbnail'] = $ossClient->signImageUrl($object, OssClient::getThumbnailImgStylename());
            Db::commit();

            return Result::success(ArrUtil::keyToCamel($data));
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
    public static function isFriend(int $selfId, int $targetId): int
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
    public static function setFriendAlias(int $chatroomId, string $alias): Result
    {
        $userId = User::getId();
        if (!$userId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

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

        $chatMember = ChatMemberModel::where('chatroom_id', '=', $chatroomId)
            ->where('user_id', '<>', $userId)->find();

        if (!$chatMember) {
            return new Result(Result::CODE_ERROR_UNKNOWN, '该私聊聊天室没有没有其他成员');
        }

        if (!$alias) {
            $alias = User::getUsernameById($chatMember->user_id);
        }

        $chatMember->nickname = $alias;
        $chatMember->save();

        return Result::success($alias);
    }
}
