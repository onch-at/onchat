<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use think\facade\Event;
use think\helper\Str;

/**
 * Socket.io 事件分发器
 * 由于think-swoole v3.1.0更新了socket.io，
 * 所有socket event集中发射到swoole.websocket.Event，
 * 因此我们需要自行分发事件
 */
class SocketEventDispatcher extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        // 这里的data支持多参数，一般只需要第一个参数，故直接解构出第一个参数
        ['type' => $type, 'data' => [$data]] = $event;

        $user = $this->getUser();

        if ($user && !$this->throttleTable->try($user['id'])) {
            return $this->websocket->emit($type, Result::create(Result::CODE_ERROR_HIGH_FREQUENCY));
        }

        Event::trigger('swoole.websocket.Event.' . Str::studly($type),  $data);
    }
}
