<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use app\service\Chat as ChatService;

class ChatRequestReject extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, ChatService $chatService)
    {
        ['requestId' => $requestId, 'reason' => $reason] = $event;

        $user = $this->getUser();

        $result = $chatService->reject($$requestId, $user['id'], $reason);

        $this->websocket->emit('chat_request_reject', $result);

        // 如果成功拒绝申请，则尝试给申请人推送消息
        if ($result->code !== Result::CODE_SUCCESS) {
            return false;
        }

        // 拿到申请人的FD
        $applicantFd = $this->fdTable->getFd($result->data['applicantId']);
        $applicantFd && $this->websocket->setSender($applicantFd)->emit('chat_request_reject', $result);
    }
}
