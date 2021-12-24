<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\User as UserService;
use app\table\Throttle as ThrottleTable;
use app\table\User as UserTable;
use think\swoole\Websocket;

class Unload extends SocketEventHandler
{
    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(Websocket $socket, UserService $userService, UserTable $userTable, ThrottleTable $throttleTable)
    {
        $user = $this->getUser();

        if ($user) {
            $userId = $user['id'];

            $userTable->del($socket->getSender());
            $throttleTable->del($userId);

            $chatrooms = $userService->getChatrooms($userId);

            // 退出房间
            foreach ($chatrooms as $chatroom) {
                $socket->leave(SocketRoomPrefix::CHATROOM . $chatroom->id);
            }

            $socket->leave(SocketRoomPrefix::USER . $userId);
        }
    }
}
