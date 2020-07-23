<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\handler\User as UserHandler;
use app\core\handler\Chatroom as ChatroomHandler;

class RevokeMsg extends BaseListener
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        $user = $this->getUser();

        $this->websocket->to(parent::ROOM_CHATROOM . $event['chatroomId'])
            ->emit("revoke_msg", ChatroomHandler::revokeMsg($event['chatroomId'], $user->id, $event['msgId']));
    }
}
