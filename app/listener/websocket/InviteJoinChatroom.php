<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use app\core\service\ChatInvitation;
use app\core\service\User as UserService;
use app\core\service\Chatroom as ChatroomService;
use app\core\service\Message as MessageService;

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

        $result = ChatInvitation::invite($user['id'], $event['chatroomId'], $event['chatroomIdList']);

        $this->websocket->emit('invite_join_chatroom', $result);

        if ($result->code !== Result::CODE_SUCCESS) {
            return;
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
            $this->websocket->to(parent::ROOM_CHATROOM . $chatroomId)
                ->emit('message', ChatroomService::setMessage($user['id'], $msg));
        }
    }
}
