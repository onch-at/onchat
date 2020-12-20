<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\service\ChatInvitation;
use app\core\service\User as UserService;
use app\core\service\Chatroom as ChatroomService;

class InviteJoinChatroom extends BaseListener
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        if (!$this->isEstablished()) {
            return false;
        }

        $user = $this->getUserByFd();

        ChatInvitation::invitation($user['id'], $event['chatroomId'], $event['chatroomIdList']);
    }
}
