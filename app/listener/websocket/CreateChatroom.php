<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\core\Result;
use app\service\Chatroom as ChatroomService;

class CreateChatroom extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, ChatroomService $chatroomService)
    {
        ['name' => $name, 'description' => $description] = $event;

        $user = $this->getUser();

        $result = $chatroomService->create($name, $description, $user['id'], $user['username']);

        $this->websocket->emit(SocketEvent::CREATE_CHATROOM, $result);

        if ($result->code === Result::CODE_SUCCESS) {
            $this->websocket->join(parent::ROOM_CHATROOM . $result->data['data']['chatroomId']);
        }
    }
}
