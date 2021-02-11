<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use think\helper\Str;
use think\facade\Event;
use app\util\Throttle as ThrottleUtil;

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
        ['type' => $type, 'data' => $data] = $event;

        $user = $this->getUser();

        if ($user && !ThrottleUtil::try($user['id'])) {
            return $this->websocket->emit($type, new Result(Result::CODE_ERROR_HIGH_FREQUENCY));
        }

        Event::trigger('swoole.websocket.Event.' . Str::studly($type),  $data);
    }
}
