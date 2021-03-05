<?php

declare(strict_types=1);

namespace app\facade;

use app\service\Friend;
use think\Facade;

/**
 * @see app\service\Friend
 */
class FriendService extends Facade
{
    protected static function getFacadeClass()
    {
        return Friend::class;
    }
}
