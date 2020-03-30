<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\common\handler\Chatroom as ChatroomHandler;
use app\common\Result;

class Chatroom extends BaseController
{
    public function getRecords(int $id, int $page = 1): Result
    {
        return ChatroomHandler::getRecords($id, $page);
    }
}
