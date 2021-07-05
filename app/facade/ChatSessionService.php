<?php

declare(strict_types=1);

namespace app\facade;

use app\service\ChatSession;
use think\Facade;

/**
 * @see app\service\ChatSession
 */
class ChatSessionService extends Facade
{
    protected static function getFacadeClass()
    {
        return ChatSession::class;
    }
}
