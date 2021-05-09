<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\service\Chat as ChatService;

class ChatRequestReject extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(ChatService $chatService, $event)
    {
        ['requestId' => $requestId, 'reason' => $reason] = $event;

        $user = $this->getUser();

        $result = $chatService->reject($requestId, $user['id'], $reason);

        $this->websocket->emit(SocketEvent::CHAT_REQUEST_REJECT, $result);

        // 如果成功拒绝申请，则尝试给申请人推送消息
        if (!$result->isSuccess()) {
            return false;
        }

        // 拿到申请人的FD
        $requesterFd = $this->fdTable->getFd($result->data['requesterId']);
        $requesterFd && $this->websocket->setSender($requesterFd)->emit(SocketEvent::CHAT_REQUEST_REJECT, $result);
    }
}
