<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\facade\Session;
use think\swoole\Websocket;
use app\service\User as UserService;

class Connect extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        trace('Connect');
    }
}
