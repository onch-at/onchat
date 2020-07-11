<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\handler\User as UserHandler;
use app\core\handler\Friend as FriendHandler;
use app\core\Result;

class FriendRequestAgree extends BaseListener
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        parent::initSession();
        $userId = parent::getUserId();
        // $username = parent::getUsername();

        $result = FriendHandler::agreeRequest($event['friendRequestId'], $userId, $event['selfAlias']);

        $chatroomId = $result->data['chatroomId'];
        $this->websocket->join(parent::ROOM_CHATROOM . $chatroomId);
        $this->websocket->emit('friend_request_agree', $result);

        // 如果成功同意申请，则尝试给申请人推送消息
        if ($result->code != Result::CODE_SUCCESS) {
            return;
        }

        // 拿到申请人的FD
        $fd = UserHandler::getWebSocketFileDescriptorByUserId($result->data['selfId']);
        if ($fd) {
            // 加入新的聊天室
            $this->websocket->setSender($fd)->join(parent::ROOM_CHATROOM . $chatroomId);
            $this->websocket->setSender($fd)->emit('friend_request_agree', $result);
        }
    }
}
