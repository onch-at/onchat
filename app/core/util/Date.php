<?php

declare(strict_types=1);

namespace app\core\util;

class Date
{
    /**
     * 返回当前毫秒级时间戳
     *
     * @return integer
     */
    public static function now(): int
    {
        return (int) (microtime(true) * 1000);
    }
}
