<?php

declare(strict_types=1);

namespace app\facade;

use think\Facade;
use app\service\Index;

class IndexService extends Facade
{
    protected static function getFacadeClass()
    {
        return Index::class;
    }
}
