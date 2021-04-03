<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\service\User as UserService;

class Unload extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, UserService $userService)
    {
        $userId = $this->getUser()['id'];

        if (!$userId) return false;

        $this->userTable->del($this->fd);
        $this->fdTable->del($userId);

        $this->throttleTable->del($userId);

        $chatrooms = $userService->getChatrooms($userId);

        // 退出房间
        foreach ($chatrooms as $chatroom) {
            $this->websocket->leave(parent::ROOM_CHATROOM . $chatroom['id']);
        }

        $this->websocket->leave(parent::ROOM_FRIEND_REQUEST . $userId);
        $this->websocket->leave(parent::ROOM_CHAT_REQUEST . $userId);
    }
}
