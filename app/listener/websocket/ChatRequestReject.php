<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\Chat as ChatService;
use think\facade\Validate;
use think\validate\ValidateRule;

class ChatRequestReject extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'requestId' => ValidateRule::must()->integer(),
            'reason'    => ValidateRule::has(true),
        ])->check($data);
    }

    /**
     * 事件监听处理.
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
        if ($result->isError()) {
            return false;
        }

        $this->websocket->to(SocketRoomPrefix::USER . $result->data['requesterId'])
            ->emit(SocketEvent::CHAT_REQUEST_REJECT, $result);
    }
}
