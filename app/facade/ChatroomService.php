<?php

declare(strict_types=1);

namespace app\facade;

use app\service\Chatroom;
use think\Facade;

/**
 * @see app\service\Chatroom
 */
class ChatroomService extends Facade
{
    protected static function getFacadeClass()
    {
        return Chatroom::class;
    }
}
