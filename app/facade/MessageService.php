<?php

declare(strict_types=1);

namespace app\facade;

use think\Facade;
use app\service\Message;

class MessageService extends Facade
{
    protected static function getFacadeClass()
    {
        return Message::class;
    }
}
