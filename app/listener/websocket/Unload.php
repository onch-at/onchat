<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\util\Redis as RedisUtil;
use app\core\service\User as UserService;

class Unload extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        $user = $this->getUser();
        RedisUtil::removeFdUserPair($this->fd);
        RedisUtil::removeUserIdFdPair($user['id']);

        $chatrooms = UserService::getChatrooms($user['id']);

        // 退出房间
        foreach ($chatrooms as $chatroom) {
            $this->websocket->leave(parent::ROOM_CHATROOM . $chatroom['id']);
        }

        $this->websocket->leave(parent::ROOM_FRIEND_REQUEST . $user['id']);
        $this->websocket->leave(parent::ROOM_CHAT_REQUEST . $user['id']);
    }
}
