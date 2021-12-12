<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\MessageType;
use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\entity\ChatInvitationMessage;
use app\entity\Message as MessageEntity;
use app\service\Chat as ChatService;
use app\service\ChatRecord as ChatRecordService;
use think\facade\Validate;
use think\validate\ValidateRule;

class InviteJoinChatroom extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'chatroomId'     => ValidateRule::must()->integer(),
            'chatroomIdList' => ValidateRule::must()->array(),
        ])->check($data);
    }

    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(ChatService $chatService, ChatRecordService $chatRecordService, $event)
    {
        ['chatroomId' => $chatroomId, 'chatroomIdList' => $chatroomIdList] = $event;

        $user = $this->getUser();

        $result = $chatService->invite($user['id'], $chatroomId, $chatroomIdList);

        $this->websocket->emit(SocketEvent::INVITE_JOIN_CHATROOM, $result);

        if ($result->isFail()) {
            return false;
        }

        $message = new MessageEntity(MessageType::CHAT_INVITATION);
        $message->userId = $user['id'];
        $message->data = new ChatInvitationMessage($chatroomId);

        // 给每个受邀者发消息
        foreach ($result->data as $chatroomId) {
            $message->chatroomId = $chatroomId;
            $this->websocket
                ->to(SocketRoomPrefix::CHATROOM . $chatroomId)
                ->emit(SocketEvent::MESSAGE, $chatRecordService->addRecord($message));
        }
    }
}
