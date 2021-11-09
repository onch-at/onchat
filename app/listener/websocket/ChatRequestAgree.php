<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\Chat as ChatService;
use think\facade\Validate;
use think\validate\ValidateRule;

class ChatRequestAgree extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'requestId' => ValidateRule::must()->integer(),
        ])->check($data);
    }

    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(ChatService $chatService, array $event)
    {
        ['requestId' => $requestId] = $event;

        $user = $this->getUser();

        $result = $chatService->agree($requestId, $user['id']);

        $this->websocket->emit(SocketEvent::CHAT_REQUEST_AGREE, $result);

        // 如果成功同意申请，则尝试给申请人推送消息
        if ($result->isError()) {
            return false;
        }

        $chatSession = $result->data[1];
        $requesterFds = $this->room->getClients(SocketRoomPrefix::USER . $chatSession['userId']);

        foreach ($requesterFds as $fd) {
            $this->room->add($fd, SocketRoomPrefix::CHATROOM . $chatSession['data']['chatroomId']);
        }

        $this->websocket->to(SocketRoomPrefix::USER . $chatSession['userId'])
            ->emit(SocketEvent::CHAT_REQUEST_AGREE, $result);
    }
}
