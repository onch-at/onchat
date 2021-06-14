<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\service\ChatRecord as ChatRecordService;

class RevokeMessage extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(ChatRecordService $chatRecordService, $event)
    {
        ['chatroomId' => $chatroomId, 'id' => $id] = $event;

        $user = $this->getUser();
        $result = $chatRecordService->revokeRecord($id, $user['id'], $chatroomId);

        $this->websocket->to(SocketRoomPrefix::CHATROOM . $chatroomId)
            ->emit(SocketEvent::REVOKE_MESSAGE, $result);
    }
}
