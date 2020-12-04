<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\service\User as UserService;
use app\core\service\Friend as FriendService;
use app\core\Result;

class FriendRequest extends BaseListener
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

        $result = FriendService::request(
            $user['id'],
            $event['userId'],
            $user['username'],
            $event['requestReason'],
            $event['targetAlias'],
        );

        $this->websocket->emit('friend_request', $result);

        // 如果成功发出申请，则尝试给被申请人推送消息
        if ($result->code === Result::CODE_SUCCESS) {
            $this->websocket->to(parent::ROOM_FRIEND_REQUEST . $event['userId'])
                ->emit('friend_request', $result);
        }
    }
}
