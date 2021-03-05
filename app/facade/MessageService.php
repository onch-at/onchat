<?php

declare(strict_types=1);

namespace app\facade;

use app\service\Message;
use think\Facade;

/**
 * @see app\service\Message
 */
class MessageService extends Facade
{
    protected static function getFacadeClass()
    {
        return Message::class;
    }
}
