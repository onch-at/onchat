<?php

declare(strict_types=1);

namespace app\facade;

use app\common\util\Str as StrUtil;
use think\Facade;

class Str extends Facade
{
    protected static function getFacadeClass()
    {
        return StrUtil::class;
    }
}
