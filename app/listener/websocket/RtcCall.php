<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\Rtc as RtcService;
use think\facade\Validate;
use think\validate\ValidateRule;

class RtcCall extends SocketEventHandler
{

    public function verify(array $data): bool
    {
        return Validate::rule([
            'chatroomId' => ValidateRule::must()->integer()
        ])->check($data);
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(RtcService $rtcService, array $event)
    {
        $result = $rtcService->call($this->getUser()['id'], $event['chatroomId']);

        if ($result->isError()) {
            return $this->websocket->emit(SocketEvent::RTC_CALL, $result);
        }

        $this->websocket
            ->to(SocketRoomPrefix::CHATROOM . $event['chatroomId'])
            ->emit(SocketEvent::RTC_CALL, $result);
    }
}
