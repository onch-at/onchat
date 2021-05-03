<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\service\Chatroom as ChatroomService;

class Message extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, ChatroomService $chatroomService)
    {
        ['msg' => $msg] = $event;

        $user = $this->getUser();
        // TODO 群聊的头像
        $this->websocket
            ->to(parent::ROOM_CHATROOM . $msg['chatroomId'])
            ->emit(SocketEvent::MESSAGE, $chatroomService->addMessage($user['id'], $msg));
    }
}
