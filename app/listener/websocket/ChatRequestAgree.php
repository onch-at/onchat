<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use app\core\util\Redis as RedisUtil;
use app\core\service\Chat as ChatService;
use app\core\service\User as UserService;

class ChatRequestAgree extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        $user = $this->getUser();

        $result = ChatService::agree($event['requestId'], $user['id']);

        $this->websocket->emit('chat_request_agree', $result);

        // 如果成功同意申请，则尝试给申请人推送消息
        if ($result->code !== Result::CODE_SUCCESS) {
            return false;
        }

        $chatSession = $result->data[1];

        // 拿到申请人的FD
        $applicantFd = RedisUtil::getFdByUserId($chatSession['userId']);
        if ($applicantFd) {
            // 加入新的聊天室
            $this->websocket->setSender($applicantFd)
                ->join(parent::ROOM_CHATROOM . $chatSession['data']['chatroomId'])
                ->emit('chat_request_agree', $result);
        }
    }
}
