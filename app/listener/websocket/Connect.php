<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\Container;
use think\swoole\Websocket;
use think\facade\Session;
use app\core\handler\User as UserHandler;

class Connect extends BaseListener
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

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
