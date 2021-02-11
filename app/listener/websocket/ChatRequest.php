<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use app\service\Chat as ChatService;
use app\service\Chatroom as ChatroomService;

class ChatRequest extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, ChatService $chatService, ChatroomService $chatroomService)
    {
        ['chatroomId' => $chatroomId, 'reason' => $reason] = $event;

        $user = $this->getUser();

        $result = $chatService->request(
            $user['id'],
            $chatroomId,
            $reason
        );

        $this->websocket->emit('chat_request', $result);

        // 如果成功发出申请，则尝试给群主和群管理推送消息
        if ($result->code === Result::CODE_SUCCESS) {
            $userIdList = $chatroomService->getHostAndManagerIdList($chatroomId);

            foreach ($userIdList as $userId) {
                $this->websocket->to(parent::ROOM_CHAT_REQUEST . $userId);
            }

            $this->websocket->emit('chat_request', $result);
        }
    }
}
