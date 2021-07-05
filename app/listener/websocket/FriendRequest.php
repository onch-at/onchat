<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\Friend as FriendService;
use think\facade\Validate;
use think\validate\ValidateRule;

class FriendRequest extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'targetId'    => ValidateRule::must()->integer(),
            'targetAlias' => ValidateRule::has(),
            'reason'      => ValidateRule::has(),
        ])->check($data);
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(FriendService $friendService, $event)
    {
        ['targetId' => $targetId, 'targetAlias' => $targetAlias, 'reason' => $reason] = $event;

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
