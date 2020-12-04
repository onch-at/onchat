<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;

use app\core\service\Friend as FriendService;
use app\core\service\User as UserService;
use app\core\Result;

class Friend extends BaseController
{

    /**
     * 获取我的收到好友申请
     *
     * @return Result
     */
    public function getReceiveFriendRequests(): Result
    {
        return FriendService::getReceiveFriendRequests();
    }

    /**
     * 获取我的发起的好友申请（不包含已经同意的）
     *
     * @return Result
     */
    public function getSendFriendRequests(): Result
    {
        return FriendService::getSendFriendRequests();
    }

    /**
     * 根据被申请人UID来获取FriendRequest
     *
     * @param integer $targetId
     * @return Result
     */
    public function getFriendRequestByTargetId(int $targetId): Result
    {
        return FriendService::getFriendRequestByTargetId($targetId);
    }

    /**
     * 根据申请人UID来获取FriendRequest
     *
     * @param integer $selfId
     * @return Result
     */
    public function getFriendRequestBySelfId(int $selfId): Result
    {
        return FriendService::getFriendRequestBySelfId($selfId);
    }

    /**
     * 通过ID获取FriendRequest
     *
     * @param integer $id
     * @return Result
     */
    public function getFriendRequestById(int $id): Result
    {
        return FriendService::getFriendRequestById($id);
    }

    /**
     * 设置好友别名
     *
     * @param integer $chatroomId 私聊聊天室ID
     * @return Result
     */
    public function setFriendAlias(int $chatroomId): Result
    {
        return FriendService::setFriendAlias($chatroomId, input('put.alias/s'));
    }

    /**
     * 判断对方与自己是否为好友关系
     * 如果是好友关系，则返回私聊房间号；否则返回零
     *
     * @param integer $id 对方的用户ID
     * @return Result
     */
    public function isFriend(int $id): Result
    {
        $userId = UserService::getId();
        if (!$userId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }
        $data = FriendService::isFriend($userId, $id);
        return Result::success($data);
    }
}
