<?php

declare(strict_types=1);

namespace app\facade;

use app\service\Auth;
use think\Facade;

/**
 * @see app\service\Auth
 */
class AuthService extends Facade
{
    protected static function getFacadeClass()
    {
        return Auth::class;
    }
}
