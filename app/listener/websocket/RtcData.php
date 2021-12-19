<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\core\Result;
use think\facade\Validate;
use think\swoole\Websocket;
use think\validate\ValidateRule;

class RtcData extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'targetId' => ValidateRule::must()->integer(),
            'type'     => ValidateRule::must()->integer(),
            'value'    => ValidateRule::must()->array(),
        ])->check($data);
    }

    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(Websocket $socket, array $event)
    {
        $event['senderId'] = $this->getUser($socket)['id'];

        $socket
            ->to(SocketRoomPrefix::USER . $event['targetId'])
            ->emit(SocketEvent::RTC_DATA, Result::success($event));
    }
}
