<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\core\Result;
use app\service\Friend as FriendService;

class FriendRequest extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, FriendService $friendService)
    {
        [
            'targetId'    => $targetId,
            'targetAlias' => $targetAlias,
            'reason'      => $reason,
        ] = $event;

        $user = $this->getUser();

        $result = $friendService->request(
            $user['id'],
            $targetId,
            $reason,
            $targetAlias
        );

        $this->websocket->emit(SocketEvent::FRIEND_REQUEST, $result);

        // 如果成功发出申请，则尝试给被申请人推送消息
        if ($result->code === Result::CODE_SUCCESS) {
            $this->websocket->to(parent::ROOM_FRIEND_REQUEST . $targetId)
                ->emit(SocketEvent::FRIEND_REQUEST, $result);
        }
    }
}
