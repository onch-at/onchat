<?php

declare(strict_types=1);

namespace app\facade;

use app\service\User;
use think\Facade;

/**
 * @see app\service\User
 */
class UserService extends Facade
{
    protected static function getFacadeClass()
    {
        return User::class;
    }
}
