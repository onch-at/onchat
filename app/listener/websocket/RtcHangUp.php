<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\core\Result;
use think\facade\Validate;
use think\validate\ValidateRule;

class RtcHangUp extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'targetId' => ValidateRule::must()->integer(),
        ])->check($data);
    }

    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(array $event)
    {
        $event['senderId'] = $this->getUser()['id'];

        $this->websocket
            ->to(SocketRoomPrefix::USER . $event['targetId'])
            ->emit(SocketEvent::RTC_HANG_UP, Result::success($event));
    }
}
