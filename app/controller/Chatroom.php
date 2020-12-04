<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\core\service\Chatroom as ChatroomService;
use app\core\Result;

class Chatroom extends BaseController
{
    /**
     * 获取聊天室名称
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public function getName(int $id): Result
    {
        return ChatroomService::getName($id);
    }

    /**
     * 获取聊天室
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public function getChatroom(int $id): Result
    {
        return ChatroomService::getChatroom($id);
    }

    /**
     * 获取聊天室消息记录
     *
     * @param integer $id 聊天室ID
     * @param integer $msgId 消息ID
     * @return Result
     */
    public function getRecords(int $id, int $msgId): Result
    {
        return ChatroomService::getRecords($id, $msgId);
    }
}
