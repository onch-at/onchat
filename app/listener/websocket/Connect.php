<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\swoole\Websocket;
use think\facade\Session;
use app\core\service\User as UserService;

class Connect extends BaseListener
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
