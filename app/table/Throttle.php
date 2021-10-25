<?php

declare(strict_types=1);

namespace app\table;

use app\contract\Table;

/**
 * 频率限制器.
 */
class Throttle extends Table
{
    protected $name = 'throttle';

    /** 限制时间（秒） */
    const LIMIT_TIME = 60;
    /** 时间内次数限制 */
    const LIMIT_COUNT = 30;

    /**
     * 设置行的数据.
     *
     * @param int $userId
     * @param int $time
     * @param int $count
     *
     * @return bool
     */
    public function set(int $userId, int $time, int $count): bool
    {
        return $this->table->set((string) $userId, [
            'time'  => $time,
            'count' => $count,
        ]);
    }

    /**
     * 根据用户ID进行尝试.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function try(int $userId): bool
    {
        $data = $this->get($userId);

        if (!$data) {
            return $this->reset($userId);
        }

        // 如果当前时间在首次计数的时间内
        if (time() < $data['time'] + self::LIMIT_TIME) {
            if ($data['count'] >= self::LIMIT_COUNT) {
                return false;
            }

            // 增加次数
            $this->table->incr((string) $userId, 'count');

            return true;
        }

        return $this->reset($userId);
    }

    /**
     * 重置某个IP的数据.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function reset(int $userId): bool
    {
        return $this->set($userId, time(), 1);
    }
}
