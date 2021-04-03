<?php

declare(strict_types=1);

namespace app\facade;

use app\table\Throttle;
use think\Facade;

class ThrottleTable extends Facade
{
    protected static function getFacadeClass()
    {
        return Throttle::class;
    }
}
