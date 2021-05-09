<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\service\Chatroom as ChatroomService;

class RevokeMessage extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(ChatroomService $chatroomService, $event)
    {
        ['chatroomId' => $chatroomId, 'msgId' => $msgId] = $event;

        $user = $this->getUser();
        $result = $chatroomService->revokeMessage($chatroomId, $user['id'], $msgId);

        $this->websocket->to(SocketRoomPrefix::CHATROOM . $chatroomId)
            ->emit(SocketEvent::REVOKE_MESSAGE, $result);
    }
}
