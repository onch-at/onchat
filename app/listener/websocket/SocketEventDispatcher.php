<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use think\helper\Str;
use think\facade\Event;
use app\core\util\Throttle as ThrottleUtil;

/**
 * Socket.io 事件分发器
 * 由于think-swoole v3.1.0更新了socket.io，
 * 所有socket event集中发射到swoole.websocket.Event，
 * 因此我们需要自行分发事件
 */
class SocketEventDispatcher extends BaseListener
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        if (!ThrottleUtil::try($this->getClientIP())) {
            return $this->websocket->emit($event['type'], new Result(Result::CODE_ERROR_HIGH_FREQUENCY));
        }

        Event::trigger('swoole.websocket.Event.' . Str::studly($event['type']),  $event['data']);
    }
}
