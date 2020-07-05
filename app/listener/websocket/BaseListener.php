<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\Container;
use think\swoole\Websocket;
use think\facade\Session;
use app\core\handler\User as UserHandler;

abstract class BaseListener
{
    protected $websocket;

    /** 聊天室 */
    const ROOM_CHATROOM = 'CHATROOM:';

    /**
     * 注入容器管理类，从容器中取出Websocket类，或者也可以直接注入Websocket类
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->websocket = $container->make(Websocket::class);
    }

    /**
     * 初始化Session
     *
     * @return void
     */
    protected function initSession()
    {
        Session::setId(UserHandler::getSessIdBytWebSocketFileDescriptor($this->websocket->getSender()));
        Session::init();
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    protected abstract function handle($event);
}
