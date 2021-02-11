<?php

declare(strict_types=1);

namespace app\facade;

use think\Facade;
use app\service\User;

class UserService extends Facade
{
    protected static function getFacadeClass()
    {
        return User::class;
    }
}
