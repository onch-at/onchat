<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\Chat as ChatService;
use app\service\Chatroom as ChatroomService;
use think\facade\Validate;
use think\swoole\Websocket;
use think\validate\ValidateRule;

class ChatRequest extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'chatroomId' => ValidateRule::must()->integer(),
            'reason'     => ValidateRule::has(true),
        ])->check($data);
    }

    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(
        array $event,
        Websocket $socket,
        ChatService $chatService,
        ChatroomService $chatroomService
    ) {
        ['chatroomId' => $chatroomId, 'reason' => $reason] = $event;

        $user = $this->getUser($socket);

        $result = $chatService->request($user['id'], $chatroomId, $reason);

        $socket->emit(SocketEvent::CHAT_REQUEST, $result);

        // 如果成功发出申请，则尝试给群主和群管理推送消息
        if ($result->isSuccess()) {
            $userIdList = $chatroomService->getHostAndManagerIdList($chatroomId);

            foreach ($userIdList as $userId) {
                $socket->to(SocketRoomPrefix::USER . $userId)->emit(SocketEvent::CHAT_REQUEST, $result);
            }
        }
    }
}
