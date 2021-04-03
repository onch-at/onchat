<?php

declare(strict_types=1);

namespace app\facade;

use app\table\Fd;
use think\Facade;

class FdTable extends Facade
{
    protected static function getFacadeClass()
    {
        return Fd::class;
    }
}
