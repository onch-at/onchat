<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\service\User as UserService;
use app\core\service\Chatroom as ChatroomService;

class CreateChatroom extends BaseListener
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

        $result = ChatroomService::create($event['name'], $event['description'], $user['id'], $user['username']);

        $this->websocket->emit('create_chatroom', $result);

        if ($result->code === 0) {
            $this->websocket->join(parent::ROOM_CHATROOM . $result->data['chatroomId']);
        }
    }
}
