<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\Container;
use think\swoole\Websocket;
use think\facade\Session;
use app\core\handler\User as UserHandler;

class UserLeave
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
        Session::setId($event['sessId']);
        Session::init();
        $this->websocket->join($event['chatroomId']);
        $this->websocket->to($event['chatroomId'])->emit("abc", UserHandler::getChatrooms(Session::get(UserHandler::SESSION_USER_LOGIN . '.id')));
        $this->websocket->to($event['chatroomId'])->emit("abc", 12345);
    }
}