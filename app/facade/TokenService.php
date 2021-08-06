<?php

declare(strict_types=1);

namespace app\facade;

use app\service\Token;
use think\Facade;

/**
 * @see app\service\Token
 */
class TokenService extends Facade
{
    protected static function getFacadeClass()
    {
        return Token::class;
    }
}
