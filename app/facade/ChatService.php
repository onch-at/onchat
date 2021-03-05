<?php

declare(strict_types=1);

namespace app\facade;

use app\service\Chat;
use think\Facade;

/**
 * @see app\service\Chat
 */
class ChatService extends Facade
{
    protected static function getFacadeClass()
    {
        return Chat::class;
    }
}
