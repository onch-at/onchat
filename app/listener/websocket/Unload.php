<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\User as UserService;
use think\swoole\Websocket;

class Unload extends SocketEventHandler
{
    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(Websocket $socket, UserService $userService)
    {
        $user = $this->getUser($socket);

        if ($user) {
            $userId = $user['id'];

            $this->userTable->del($socket->getSender());
            $this->throttleTable->del($userId);

            $chatrooms = $userService->getChatrooms($userId);

            // 退出房间
            foreach ($chatrooms as $chatroom) {
                $socket->leave(SocketRoomPrefix::CHATROOM . $chatroom->id);
            }

            $socket->leave(SocketRoomPrefix::USER . $userId);
        }
    }
}
