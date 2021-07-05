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
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(ChatService $chatService, $event)
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
