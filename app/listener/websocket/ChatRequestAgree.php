<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\service\Chat as ChatService;

class ChatRequestAgree extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, ChatService $chatService)
    {
        ['requestId' => $requestId] = $event;

        $user = $this->getUser();

        $result = $chatService->agree($requestId, $user['id']);

        $this->websocket->emit(SocketEvent::CHAT_REQUEST_AGREE, $result);

        // 如果成功同意申请，则尝试给申请人推送消息
        if (!$result->isSuccess()) {
            return false;
        }

        $chatSession = $result->data[1];

        // 拿到申请人的FD
        $requesterFd = $this->fdTable->getFd($chatSession['userId']);
        if ($requesterFd) {
            // 加入新的聊天室
            $this->websocket->setSender($requesterFd)
                ->join(SocketRoomPrefix::CHATROOM . $chatSession['data']['chatroomId'])
                ->emit(SocketEvent::CHAT_REQUEST_AGREE, $result);
        }
    }
}
