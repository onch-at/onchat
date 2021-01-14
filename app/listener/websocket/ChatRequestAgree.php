<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\service\User as UserService;
use app\core\service\Chat as ChatService;
use app\core\Result;

class ChatRequestAgree extends BaseListener
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        if (!$this->isEstablished()) {
            return false;
        }

        $user = $this->getUserByFd();

        $result = ChatService::agree($event['requestId'], $user['id'], $user['username']);

        $this->websocket->emit('chat_request_agree', $result);

        // 如果成功同意申请，则尝试给申请人推送消息
        if ($result->code !== Result::CODE_SUCCESS) {
            return;
        }

        $chatSession = $result->data[1];

        // 拿到申请人的FD
        $applicantFd = $this->getFdByUserId($chatSession['userId']);
        if ($applicantFd) {
            // 加入新的聊天室
            $this->websocket
                ->setSender($applicantFd)
                ->join(parent::ROOM_CHATROOM . $chatSession['data']['chatroomId'])
                ->emit('chat_request_agree', $result);
        }
    }
}
