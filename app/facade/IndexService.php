<?php

declare(strict_types=1);

namespace app\facade;

use app\service\Index;
use think\Facade;

/**
 * @see app\service\Index
 */
class IndexService extends Facade
{
    protected static function getFacadeClass()
    {
        return Index::class;
    }
}
