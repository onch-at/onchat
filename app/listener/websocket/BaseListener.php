<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\swoole\Websocket;
use think\facade\Cache;
use app\core\service\User as UserService;

abstract class BaseListener
{
    protected $websocket;
    protected $server;
    protected $redisDriver;
    /** 当前用户的FD */
    protected $fd;
    /** SESSION的前缀 */
    protected $sessPrefix;

    /** 聊天室房间前缀 */
    const ROOM_CHATROOM = 'CHATROOM:';
    /** 好友申请房间前缀 */
    const ROOM_FRIEND_REQUEST = 'FRIEND_REQUEST:';
    /** 群聊邀请房间前缀 */
    const ROOM_CHAR_INVITATION = 'CHAR_INVITATION:';

    /** Redis Hash 名称：储存fd => user */
    const REDIS_HASH_FD_USER_PAIR = 'ONCHAT_PAIR:fd-user';
    /** Redis Hash 名称：储存uid => fd */
    const REDIS_HASH_UID_FD_PAIR = 'ONCHAT_PAIR:uid-fd';

    public function __construct(Websocket $websocket)
    {
        $this->websocket = $websocket;
        $this->server = app("think\swoole\Manager")->getServer();
        $this->redisDriver = Cache::store('redis');
        $this->fd = $this->websocket->getSender();

        $this->sessPrefix = config('session.prefix');
    }

    /**
     * 拿到session里面的东西
     * 并趁机储存fd-user
     *
     * @return void
     */
    protected function setFdUserPair(string $sessId)
    {
        $session = unserialize(unserialize($this->getRedis()->get($this->sessPrefix . $sessId)));

        $this->getRedis()->hSet(self::REDIS_HASH_FD_USER_PAIR, (string) $this->fd, serialize([
            'id'       => $session[UserService::SESSION_USER_LOGIN]['id'],
            'username' => $session[UserService::SESSION_USER_LOGIN]['username']
        ]));
    }

    /**
     * 通过fd得到user
     * user目前包含：id，username
     *
     * @return mixed
     */
    protected function getUserByFd()
    {
        return unserialize($this->getRedis()->hGet(self::REDIS_HASH_FD_USER_PAIR, (string) $this->fd));
    }

    /**
     * 删除fd-user
     *
     * @return void
     */
    protected function removeFdUserPair()
    {
        $this->getRedis()->hDel(self::REDIS_HASH_FD_USER_PAIR, (string) $this->fd);
    }

    /**
     * 设置uid-fd对
     *
     * @param integer $userId
     * @return void
     */
    protected function setUserIdFdPair(int $userId)
    {
        $this->getRedis()->hSet(self::REDIS_HASH_UID_FD_PAIR, (string) $userId, $this->fd);
    }

    /**
     * 删除uid-fd对
     *
     * @param integer $userId
     * @return void
     */
    protected function removeUserIdFdPair(int $userId)
    {
        $this->getRedis()->hDel(self::REDIS_HASH_UID_FD_PAIR, $userId);
    }

    /**
     * 通过UserId获取WebSocket FileDescriptor
     * 如果获取不到则返回数字零
     *
     * @param integer $userId
     * @return integer
     */
    protected function getFdByUserId(int $userId): int
    {
        return (int) $this->getRedis()->hGet(self::REDIS_HASH_UID_FD_PAIR, (string) $userId);
    }

    /**
     * 判断是否是正确的websocket连接
     *
     * @return bool
     */
    protected function isEstablished(): bool
    {
        return $this->server->isEstablished($this->fd);
    }

    /**
     * 返回Redis句柄对象
     *
     * @return mixed
     */
    protected function getRedis()
    {
        return $this->redisDriver->handler();
    }
}
