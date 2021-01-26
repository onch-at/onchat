<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\service\User as UserService;
use app\core\service\Chatroom as ChatroomService;

class RevokeMsg extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        $user = $this->getUser();
        $result = ChatroomService::revokeMsg($event['chatroomId'], $user['id'], $event['msgId']);

        $this->websocket->to(parent::ROOM_CHATROOM . $event['chatroomId'])
            ->emit('revoke_msg', $result);
    }
}
