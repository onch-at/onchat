<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\service\User as UserService;
use app\util\Redis as RedisUtil;
use app\util\Throttle as ThrottleUtil;

class Unload extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, UserService $userService)
    {
        $user = $this->getUser();

        if (!$user) return false;

        ['id' => $userId] = $user;

        RedisUtil::removeFdUserPair($this->fd);
        RedisUtil::removeUserIdFdPair($userId);
        ThrottleUtil::clear($userId);

        $chatrooms = $userService->getChatrooms($userId);

        // 退出房间
        foreach ($chatrooms as $chatroom) {
            $this->websocket->leave(parent::ROOM_CHATROOM . $chatroom['id']);
        }

        $this->websocket->leave(parent::ROOM_FRIEND_REQUEST . $userId);
        $this->websocket->leave(parent::ROOM_CHAT_REQUEST . $userId);
    }
}
