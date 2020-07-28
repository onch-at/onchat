<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\swoole\Websocket;
use think\cache\driver\Redis;
use app\core\handler\User as UserHandler;

abstract class BaseListener
{
    protected $websocket;
    protected $server;
    protected $redis;
    protected $fd;
    protected $sessPrefix;

    /** 聊天室房间前缀 */
    const ROOM_CHATROOM = 'CHATROOM:';
    /** 好友申请房间前缀 */
    const ROOM_FRIEND_REQUEST = 'FRIEND_REQUEST:';

    /** Redis Hash 名称：储存fd => user */
    const REDIS_HASH_FD_USER_PAIR = 'ONCHAT_PAIR:fd-user';
    /** Redis Hash 名称：储存uid => fd */
    const REDIS_HASH_UID_FD_PAIR = 'ONCHAT_PAIR:uid-fd';

    public function __construct(Websocket $websocket)
    {
        $this->websocket = $websocket;
        $this->server = app("think\swoole\Manager")->getServer();
        $this->redis = new Redis;
        $this->fd = $this->websocket->getSender();

        $this->sessPrefix = config('session.prefix');
    }

    /**
     * 拿到session里面的东西
     * 并趁机储存fd-user
     *
     * @return void
     */
    protected function initSession(string $sessId)
    {
        $session = unserialize($this->redis->get($this->sessPrefix . $sessId));

        $this->redis->hSet(self::REDIS_HASH_FD_USER_PAIR, (string) $this->fd, serialize((object) [
            'id'       => $session[UserHandler::SESSION_USER_LOGIN]['id'],
            'username' => $session[UserHandler::SESSION_USER_LOGIN]['username']
        ]));
    }

    /**
     * 通过fd得到user
     * user目前包含：id，username
     *
     * @return mixed
     */
    protected function getUser()
    {
        return unserialize($this->redis->hGet(self::REDIS_HASH_FD_USER_PAIR, (string) $this->fd));
    }

    /**
     * 删除fd-user
     *
     * @return void
     */
    protected function removeUser()
    {
        $this->redis->hDel(self::REDIS_HASH_FD_USER_PAIR, (string) $this->fd);
    }

    /**
     * 设置uid-fd对
     *
     * @param integer $userId
     * @return void
     */
    protected function setUserIdFdPair(int $userId)
    {
        $this->redis->hSet(self::REDIS_HASH_UID_FD_PAIR, (string) $userId, $this->fd);
    }

    /**
     * 删除uid-fd对
     *
     * @param integer $userId
     * @return void
     */
    protected function removeUserIdFdPair(int $userId)
    {
        $this->redis->hDel(self::REDIS_HASH_UID_FD_PAIR, $userId);
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
        return (int) $this->redis->hGet(self::REDIS_HASH_UID_FD_PAIR, (string) $userId);
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
}
