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
