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

    /** 聊天室房间前缀 */
    const ROOM_CHATROOM = 'CHATROOM:';
    /** 好友申请房间前缀 */
    const ROOM_FRIEND_REQUEST = 'FRIEND_REQUEST:';

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
    protected function initSession(string $sessId = null)
    {
        Session::setId($sessId ?:UserHandler::getSessIdBytWebSocketFileDescriptor($this->websocket->getSender()));
        Session::init();
    }

    protected function getUserId()
    {
        return Session::get(UserHandler::SESSION_USER_LOGIN . '.id');
    }

    protected function getUsername()
    {
        return Session::get(UserHandler::SESSION_USER_LOGIN . '.username');
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    protected abstract function handle($event);
}
