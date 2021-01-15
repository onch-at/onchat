<?php

declare(strict_types=1);

namespace app\core\util;

use think\facade\Cache;
use think\facade\Config;
use app\core\service\User as UserService;

class Redis
{
    /** Redis Hash 名称：储存fd => user */
    const REDIS_HASH_FD_USER_PAIR = 'ONCHAT_PAIR:fd-user';
    /** Redis Hash 名称：储存uid => fd */
    const REDIS_HASH_UID_FD_PAIR = 'ONCHAT_PAIR:uid-fd';

    public static function getRedis()
    {
        return Cache::store('redis')->handler();
    }

    /**
     * 储存fd-user对
     *
     * @param integer $fd
     * @param string $sessId
     * @return boolean
     */
    public static function setFdUserPair(int $fd, string $sessId): bool
    {
        $redis = self::getRedis();
        $sessPrefix = Config::get('session.prefix');
        $data = $redis->get($sessPrefix . $sessId);

        if (!$data) {
            return false;
        }

        $session = unserialize(unserialize($data));
        $userInfo = $session[UserService::SESSION_USER_LOGIN];

        return $redis->hSet(self::REDIS_HASH_FD_USER_PAIR, (string) $fd, serialize([
            'id'       => $userInfo['id'],
            'username' => $userInfo['username']
        ])) !== false;
    }

    /**
     * 通过fd得到user
     * user目前包含：id，username
     *
     * @param integer $fd
     * @return array|null
     */
    public static function getUserByFd(int $fd): ?array
    {
        $redis = self::getRedis();
        $data = $redis->hGet(self::REDIS_HASH_FD_USER_PAIR, (string) $fd);

        if (!$data) {
            return null;
        }

        return unserialize($data);
    }

    /**
     * 通过用户ID获取User
     *
     * @param integer $userId
     * @return array|null
     */
    public static function getUserByUserId(int $userId): ?array
    {
        $fd = self::getFdByUserId($userId);

        if ($fd === 0) {
            return null;
        }

        return self::getUserByFd($fd);
    }

    /**
     * 删除fd-user对
     *
     * @return void
     */
    public static function removeFdUserPair(int $fd)
    {
        $redis = self::getRedis();
        $redis->hDel(self::REDIS_HASH_FD_USER_PAIR, (string) $fd);
    }

    /**
     * 清空fd-user对
     *
     * @return void
     */
    public static function clearFdUserPair()
    {
        $redis = self::getRedis();
        $redis->del(self::REDIS_HASH_FD_USER_PAIR);
    }

    /**
     * 设置uid-fd对
     *
     * @param integer $userId
     * @param integer $fd
     * @return void
     */
    public static function setUserIdFdPair(int $userId, int $fd)
    {
        $redis = self::getRedis();
        $redis->hSet(self::REDIS_HASH_UID_FD_PAIR, (string) $userId, $fd);
    }

    /**
     * 删除uid-fd对
     *
     * @param integer $userId
     * @return void
     */
    public static function removeUserIdFdPair(int $userId)
    {
        $redis = self::getRedis();
        $redis->hDel(self::REDIS_HASH_UID_FD_PAIR, (string) $userId);
    }

    /**
     * 清空uid-fd对
     *
     * @return void
     */
    public static function clearUserIdFdPair()
    {
        $redis = self::getRedis();
        $redis->del(self::REDIS_HASH_UID_FD_PAIR);
    }

    /**
     * 通过UserId获取WebSocket FileDescriptor
     * 如果获取不到则返回数字零
     *
     * @param integer $userId
     * @return integer
     */
    public static function getFdByUserId(int $userId): int
    {
        $redis = self::getRedis();
        return (int) $redis->hGet(self::REDIS_HASH_UID_FD_PAIR, (string) $userId);
    }
}
