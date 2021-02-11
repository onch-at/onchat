<?php

declare(strict_types=1);

namespace app\facade;

use think\Facade;
use app\service\Chatroom;

class ChatroomService extends Facade
{
    protected static function getFacadeClass()
    {
        return Chatroom::class;
    }
}
