<?php

declare(strict_types=1);

namespace app\core;

use think\facade\Cache;

class Redis
{
    public static function create(): \Redis
    {
        return Cache::store('redis')->handler();
    }
}
