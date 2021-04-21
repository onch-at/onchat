<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\MessageType;
use app\core\Result;
use app\service\Chat as ChatService;
use app\service\Chatroom as ChatroomService;
use app\service\Message as MessageService;

class InviteJoinChatroom extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, ChatService $chatService, ChatroomService $chatroomService)
    {
        ['chatroomId' => $chatroomId, 'chatroomIdList' => $chatroomIdList] = $event;

        $user = $this->getUser();

        $result = $chatService->invite($user['id'], $chatroomId, $chatroomIdList);

        $this->websocket->emit('invite_join_chatroom', $result);

        if ($result->code !== Result::CODE_SUCCESS) {
            return false;
        }

        $msg = [
            'type' => MessageType::CHAT_INVITATION,
            'data' => [
                'chatroomId' => $chatroomId
            ]
        ];

        // 给每个受邀者发消息
        foreach ($result->data as $chatroomId) {
            $msg['chatroomId'] = $chatroomId;
            $this->websocket
                ->to(parent::ROOM_CHATROOM . $chatroomId)
                ->emit('message', $chatroomService->addMessage($user['id'], $msg));
        }
    }
}
