<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use app\core\util\Redis as RedisUtil;
use app\core\service\User as UserService;
use app\core\service\Chat as ChatService;

class ChatRequestReject extends BaseListener
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

        $user = $this->getUser();

        $result = ChatService::reject($event['requestId'], $user['id'], $event['rejectReason']);

        $this->websocket->emit('chat_request_reject', $result);

        // 如果成功拒绝申请，则尝试给申请人推送消息
        if ($result->code !== Result::CODE_SUCCESS) {
            return false;
        }

        // 拿到申请人的FD
        $applicantFd = RedisUtil::getFdByUserId($result->data['applicantId']);
        $applicantFd && $this->websocket->setSender($applicantFd)->emit('chat_request_reject', $result);
    }
}
