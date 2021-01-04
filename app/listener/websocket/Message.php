<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\service\User as UserService;
use app\core\service\Chatroom as ChatroomService;

class Message extends BaseListener
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
        // TODO 群聊的头像
        $this->websocket
            ->to(parent::ROOM_CHATROOM . $event['msg']['chatroomId'])
            ->emit('message', ChatroomService::setMessage($user['id'], $event['msg']));
    }
}
