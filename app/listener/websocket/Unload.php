<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\User as UserService;

class Unload extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return true;
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(UserService $userService)
    {
        $user = $this->getUser();

        if (!$user) return false;

        $userId = $user['id'];

        $this->userTable->del($this->fd);
        $this->throttleTable->del($userId);

        $chatrooms = $userService->getChatrooms($userId);

        // 退出房间
        foreach ($chatrooms as $chatroom) {
            $this->websocket->leave(SocketRoomPrefix::CHATROOM . $chatroom->id);
        }

        $this->websocket->leave(SocketRoomPrefix::USER . $userId);
    }
}
