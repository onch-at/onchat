<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
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

        $this->websocket->emit('chat_request_agree', $result);

        // 如果成功同意申请，则尝试给申请人推送消息
        if ($result->code !== Result::CODE_SUCCESS) {
            return false;
        }

        $chatSession = $result->data[1];

        // 拿到申请人的FD
        $applicantFd = $this->fdTable->getFd($chatSession['userId']);
        if ($applicantFd) {
            // 加入新的聊天室
            $this->websocket->setSender($applicantFd)
                ->join(parent::ROOM_CHATROOM . $chatSession['data']['chatroomId'])
                ->emit('chat_request_agree', $result);
        }
    }
}
