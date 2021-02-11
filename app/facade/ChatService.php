<?php

declare(strict_types=1);

namespace app\facade;

use think\Facade;
use app\service\Chat;

class ChatService extends Facade
{
    protected static function getFacadeClass()
    {
        return Chat::class;
    }
}
