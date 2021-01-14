<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use app\core\util\Redis as RedisUtil;
use app\core\service\User as UserService;
use app\core\service\Friend as FriendService;

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

        $user = $this->getUser();

        $result = FriendService::reject($event['friendRequestId'], $user['id'], $event['rejectReason']);

        $this->websocket->emit('friend_request_reject', $result);

        // 如果成功拒绝申请，则尝试给申请人推送消息
        if ($result->code !== Result::CODE_SUCCESS) {
            return;
        }

        // 拿到申请人的FD
        $selfFd = RedisUtil::getFdByUserId($result->data['selfId']);
        $selfFd && $this->websocket->setSender($selfFd)->emit('friend_request_reject', $result);
    }
}
