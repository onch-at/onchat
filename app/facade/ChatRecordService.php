<?php

declare(strict_types=1);

namespace app\facade;

use app\service\ChatRecord;
use think\Facade;

/**
 * @see app\service\ChatRecord
 */
class ChatRecordService extends Facade
{
    protected static function getFacadeClass()
    {
        return ChatRecord::class;
    }
}
