<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\swoole\Websocket;
use think\facade\Session;
use app\core\handler\User as UserHandler;

class Connect extends BaseListener
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        // Session::setId($event['sessId']);
        // Session::init();
        trace($event);
    }
}
