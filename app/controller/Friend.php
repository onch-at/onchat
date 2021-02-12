<?php

declare(strict_types=1);

namespace app\controller;

use think\App;
use app\core\Result;
use app\facade\UserService;
use app\service\Friend as FriendService;

class Friend extends BaseController
{
    protected $service;

    public function __construct(App $app, FriendService $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 获取我的收到好友申请
     *
     * @return Result
     */
    public function getReceiveRequests(): Result
    {
        return $this->service->getReceiveRequests();
    }

    /**
     * 获取我的发起的好友申请（不包含已经同意的）
     *
     * @return Result
     */
    public function getSendRequests(): Result
    {
        return $this->service->getSendRequests();
    }

    /**
     * 根据被申请人UID来获取FriendRequest
     *
     * @param integer $targetId
     * @return Result
     */
    public function getRequestByTargetId(int $targetId): Result
    {
        return $this->service->getRequestByTargetId($targetId);
    }

    /**
     * 根据申请人UID来获取FriendRequest
     *
     * @param integer $selfId
     * @return Result
     */
    public function getRequestBySelfId(int $selfId): Result
    {
        return $this->service->getRequestBySelfId($selfId);
    }

    /**
     * 通过ID获取FriendRequest
     *
     * @param integer $id
     * @return Result
     */
    public function getRequestById(int $id): Result
    {
        return $this->service->getRequestById($id);
    }

    /**
     * 设置好友别名
     *
     * @param integer $chatroomId 私聊聊天室ID
     * @param string $alias 别名
     * @return Result
     */
    public function setFriendAlias(int $chatroomId, string $alias): Result
    {
        return $this->service->setFriendAlias($chatroomId, $alias);
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
        $data = $this->service->isFriend($userId, $id);
        return Result::success($data);
    }
}
