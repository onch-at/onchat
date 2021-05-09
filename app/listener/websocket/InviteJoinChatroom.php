<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\MessageType;
use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\entity\ChatInvitationMessage;
use app\entity\Message as MessageEntity;
use app\service\Chat as ChatService;
use app\service\Chatroom as ChatroomService;

class InviteJoinChatroom extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(ChatService $chatService, ChatroomService $chatroomService, $event)
    {
        ['chatroomId' => $chatroomId, 'chatroomIdList' => $chatroomIdList] = $event;

        $user = $this->getUser();

        $result = $chatService->invite($user['id'], $chatroomId, $chatroomIdList);

        $this->websocket->emit(SocketEvent::INVITE_JOIN_CHATROOM, $result);

        if (!$result->isSuccess()) {
            return false;
        }

        $message = new MessageEntity(MessageType::CHAT_INVITATION);
        $message->userId = $user['id'];
        $message->data   = new ChatInvitationMessage($chatroomId);
        $message = $message->toArray();

        // 给每个受邀者发消息
        foreach ($result->data as $chatroomId) {
            $message['chatroomId'] = $chatroomId;
            $this->websocket
                ->to(SocketRoomPrefix::CHATROOM . $chatroomId)
                ->emit('message', $chatroomService->addMessage($message));
        }
    }
}
