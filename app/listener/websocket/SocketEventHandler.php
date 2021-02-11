<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\util\Redis as RedisUtil;

/**
 * Socket事件处理程序
 */
abstract class SocketEventHandler extends BaseListener
{
    /** 聊天室房间前缀 */
    const ROOM_CHATROOM = 'CHATROOM:';
    /** 好友申请房间前缀 */
    const ROOM_FRIEND_REQUEST = 'FRIEND_REQUEST:';
    /** 群聊申请房间前缀 */
    const ROOM_CHAT_REQUEST = 'CHAT_REQUEST:';

    /**
     * 获取当前user
     *
     * @return array|null
     */
    public function getUser(): ?array
    {
        return RedisUtil::getUserByFd($this->fd);
    }
}
