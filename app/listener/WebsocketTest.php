<?php

declare(strict_types=1);
namespace app\listener;

use think\Container;
use think\swoole\Websocket;


class WebsocketTest
{
    public $websocket = null;
    /**
     * 注入容器管理类，从容器中取出Websocket类，或者也可以直接注入Websocket类，
     */
    public function __construct(Container $container)
    {
        $this->websocket = $container->make(Websocket::class);
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        //回复客户端消息
        $this->websocket->emit("testcallback", ['aaaaa' => 1, 'getdata' => $event]);
        //不同于HTTP模式，这里可以进行多次发送
        $this->websocket->emit("testcallback", ['aaaaa' => 1, 'getdata' => $event['asd']]);
    }
}
