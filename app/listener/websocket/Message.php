<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
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
        $msg['userId'] = $this->getUser()['id'];
        $result = $chatroomService->addMessage($msg);

        if (!$result->isSuccess()) {
            return $this->websocket->emit(SocketEvent::MESSAGE, $result);
        }

        // TODO 群聊的头像
        $this->websocket
            ->to(SocketRoomPrefix::CHATROOM . $msg['chatroomId'])
            ->emit(SocketEvent::MESSAGE, $result);
    }
}
