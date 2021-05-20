<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketRoomPrefix;
use app\service\User as UserService;

class Unload extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(UserService $userService)
    {
        $userId = $this->getUser()['id'];

        if (!$userId) return false;

        $this->userTable->del($this->fd);
        $this->fdTable->del($userId);

        $this->throttleTable->del($userId);

        $chatrooms = $userService->getChatrooms($userId);

        // 退出房间
        foreach ($chatrooms as $chatroom) {
            $this->websocket->leave(SocketRoomPrefix::CHATROOM . $chatroom->id);
        }

        $this->websocket->leave(SocketRoomPrefix::FRIEND_REQUEST . $userId);
        $this->websocket->leave(SocketRoomPrefix::CHAT_REQUEST . $userId);
    }
}
