<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\service\Friend as FriendService;

class FriendRequest extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(FriendService $friendService, $event)
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
        if ($result->isSuccess()) {
            $this->websocket->to(SocketRoomPrefix::FRIEND_REQUEST . $targetId)
                ->emit(SocketEvent::FRIEND_REQUEST, $result);
        }
    }
}
