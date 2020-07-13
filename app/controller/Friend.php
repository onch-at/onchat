<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;

use app\core\handler\Friend as FriendHandler;
use app\core\handler\User as UserHandler;
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
        return FriendHandler::getReceiveFriendRequests();
    }

    /**
     * 获取我的发起的好友申请（不包含已经同意的）
     *
     * @return Result
     */
    public function getSendFriendRequests(): Result
    {
        return FriendHandler::getSendFriendRequests();
    }

    /**
     * 根据被申请人UID来获取FriendRequest
     *
     * @param integer $targetId
     * @return Result
     */
    public function getFriendRequestByTargetId(int $targetId): Result
    {
        return FriendHandler::getFriendRequestByTargetId($targetId);
    }

    /**
     * 根据申请人UID来获取FriendRequest
     *
     * @param integer $selfId
     * @return Result
     */
    public function getFriendRequestBySelfId(int $selfId): Result
    {
        return FriendHandler::getFriendRequestBySelfId($selfId);
    }

    /**
     * 通过ID获取FriendRequest
     *
     * @param integer $id
     * @return Result
     */
    public function getFriendRequestById(int $id): Result
    {
        return FriendHandler::getFriendRequestById($id);
    }

    /**
     * 设置好友别名
     *
     * @param integer $chatroomId 私聊聊天室ID
     * @return Result
     */
    public function setFriendAlias(int $chatroomId): Result
    {
        if (empty(input('put.alias'))) { // 如果参数缺失
            return new Result(Result::CODE_ERROR_PARAM);
        }

        return FriendHandler::setFriendAlias($chatroomId, input('put.alias/s'));
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
        $userId = UserHandler::getId();
        if (!$userId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }
        $data = FriendHandler::isFriend($userId, $id);
        return new Result(Result::CODE_SUCCESS, null, $data);
    }
}
