<?php

declare(strict_types=1);

namespace app\util;

/**
 * 频率限制器
 */
class Throttle
{
    /** Redis Hash 名称 储存uid => 数据 */
    const REDIS_HASH_KEY = 'ONCHAT_PAIR:uid-throttle';
    /** 限制时间（秒） */
    const LIMIT_TIME = 60;
    /** 时间内次数限制 */
    const LIMIT_COUNT = 40;

    /**
     * 根据用户ID进行尝试
     *
     * @param int $userId
     * @return boolean
     */
    public static function try(int $userId): bool
    {
        $redis = Redis::getRedis();
        $data  = $redis->hGet(self::REDIS_HASH_KEY, (string) $userId);

        if (!$data) {
            return self::reset($userId);
        }

        $data = unserialize($data);

        // 如果当前时间在首次计数的时间内
        if (time() < $data['time'] + self::LIMIT_TIME) {
            if ($data['count'] >= self::LIMIT_COUNT) {
                return false;
            }

            // 增加次数
            $data['count'] += 1;
            $redis->hSet(self::REDIS_HASH_KEY, (string) $userId, serialize($data));

            return true;
        }

        return self::reset($userId);
    }

    /**
     * 重置某个IP的数据
     *
     * @param int $userId
     * @return boolean
     */
    public static function reset(int $userId): bool
    {
        $redis = Redis::getRedis();

        return $redis->hSet(self::REDIS_HASH_KEY, (string) $userId, serialize([
            'time'  => time(),
            'count' => 1
        ])) !== false;
    }

    /**
     * 清理某个用户的数据
     *
     * @param integer $userId
     * @return void
     */
    public static function clear(int $userId)
    {
        $redis = Redis::getRedis();
        $redis->hDel(self::REDIS_HASH_KEY, (string) $userId);
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
