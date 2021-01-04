<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\service\User as UserService;
use app\core\service\Friend as FriendService;
use app\core\Result;

class FriendRequestReject extends BaseListener
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

        $result = FriendService::reject($event['friendRequestId'], $user['id'], $user['username'], $event['rejectReason']);

        $this->websocket->emit('friend_request_reject', $result);

        // 如果成功拒绝申请，则尝试给申请人推送消息
        if ($result->code != Result::CODE_SUCCESS) {
            return;
        }

        // 拿到申请人的FD
        $selfFd = $this->getFdByUserId($result->data['selfId']);
        $selfFd && $this->websocket->setSender($selfFd)->emit('friend_request_reject', $result);
    }
}
