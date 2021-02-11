<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\service\User as UserService;
use app\service\Chatroom as ChatroomService;

class RevokeMsg extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, ChatroomService $chatroomService)
    {
        ['chatroomId' => $chatroomId, 'msgId' => $msgId] = $event;

        $user = $this->getUser();
        $result = $chatroomService->revokeMsg($chatroomId, $user['id'], $msgId);

        $this->websocket->to(parent::ROOM_CHATROOM . $chatroomId)
            ->emit('revoke_msg', $result);
    }
}
