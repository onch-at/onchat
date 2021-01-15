<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use app\core\util\Redis as RedisUtil;
use app\core\service\Chat as ChatService;
use app\core\service\User as UserService;
use app\core\service\Message as MessageService;
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

        $user = $this->getUser();

        $result = ChatService::invite($user['id'], $event['chatroomId'], $event['chatroomIdList']);

        $this->websocket->emit('invite_join_chatroom', $result);

        if ($result->code !== Result::CODE_SUCCESS) {
            return false;
        }

        $msg = [
            'type' => MessageService::TYPE_CHAT_INVITATION,
            'data' => [
                'chatroomId' => $event['chatroomId']
            ]
        ];

        // 给每个受邀者发消息
        foreach ($result->data as $chatroomId) {
            $msg['chatroomId'] = $chatroomId;
            $this->websocket->to(parent::ROOM_CHATROOM . $chatroomId);
        }

        $this->websocket->emit('message', ChatroomService::setMessage($user['id'], $msg));
    }
}
