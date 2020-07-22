<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\handler\User as UserHandler;

class Unload extends BaseListener
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        parent::initSession();
        $userId = UserHandler::getId();
        $chatrooms = UserHandler::getChatrooms($userId)->data;

        foreach ($chatrooms as $chatroom) {
            $this->websocket->leave(parent::ROOM_CHATROOM . $chatroom['id']);
        }

        $this->websocket->leave(parent::ROOM_FRIEND_REQUEST . $userId);

        $fd = $this->websocket->getSender();
        UserHandler::removeWebSocketFileDescriptorSessIdPair($fd);
        UserHandler::removeUserIdWebSocketFileDescriptorPair($userId);
    }
}
