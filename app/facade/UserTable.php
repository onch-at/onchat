<?php

declare(strict_types=1);

namespace app\facade;

use app\table\User;
use think\Facade;

class UserTable extends Facade
{
    protected static function getFacadeClass()
    {
        return User::class;
    }
}
