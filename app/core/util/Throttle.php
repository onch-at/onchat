<?php

declare(strict_types=1);

namespace app\core\util;

/**
 * 频率限制器
 */
class Throttle
{
    /** Redis Hash 名称 储存IP => 数据 */
    const REDIS_HASH_KEY = 'ONCHAT_PAIR:ip-throttle';
    /** 限制时间（秒） */
    const LIMIT_TIME = 60;
    /** 时间内次数限制 */
    const LIMIT_COUNT = 40;

    /**
     * 根据IP进行尝试
     *
     * @param string $ip
     * @return boolean
     */
    public static function try(string $ip): bool
    {
        $redis = Redis::getRedis();
        $data  = $redis->hGet(self::REDIS_HASH_KEY, $ip);

        if (!$data) {
            return self::reset($ip);
        }

        $data = unserialize($data);

        // 如果当前时间在首次计数的时间内
        if (time() < $data['time'] + self::LIMIT_TIME) {
            if ($data['count'] > self::LIMIT_COUNT) {
                return false;
            }

            // 增加次数
            $data['count'] += 1;
            $redis->hSet(self::REDIS_HASH_KEY, $ip, serialize($data));

            return true;
        }

        return self::reset($ip);
    }

    /**
     * 重置某个IP的数据
     *
     * @param string $ip
     * @return boolean
     */
    public static function reset(string $ip): bool
    {
        $redis = Redis::getRedis();

        return $redis->hSet(self::REDIS_HASH_KEY, $ip, serialize([
            'time'  => time(),
            'count' => 1
        ])) !== false;
    }

    /**
     * 清理所有数据
     *
     * @return void
     */
    public static function clearAll()
    {
        $redis = Redis::getRedis();
        $redis->del(self::REDIS_HASH_KEY);
    }
}
