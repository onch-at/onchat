<?php

declare(strict_types=1);

namespace app\core\util;

use think\facade\Db;
use think\db\Raw;

class Sql
{
    /**
     * 原生表达式：毫秒级时间戳
     *
     * @return Raw
     */
    public static function rawTimestamp(): Raw
    {
        return Db::raw('UNIX_TIMESTAMP()*1000');
    }
}
