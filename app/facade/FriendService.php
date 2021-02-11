<?php

declare(strict_types=1);

namespace app\facade;

use think\Facade;
use app\service\Friend;

class FriendService extends Facade
{
    protected static function getFacadeClass()
    {
        return Friend::class;
    }
}
