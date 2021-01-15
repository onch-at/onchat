<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use app\core\util\Redis as RedisUtil;
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

        $user = $this->getUser();

        $result = ChatroomService::create($event['name'], $event['description'], $user['id'], $user['username']);

        $this->websocket->emit('create_chatroom', $result);

        if ($result->code === Result::CODE_SUCCESS) {
            $this->websocket->join(parent::ROOM_CHATROOM . $result->data['chatroomId']);
        }
    }
}
