<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\contract\SocketEventHandler;
use app\core\Result;
use app\table\Throttle as ThrottleTable;
use app\table\User as UserTable;
use app\util\Str as StrUtil;
use think\Config;
use think\Container;
use think\facade\Event;
use think\swoole\Websocket;
use think\swoole\websocket\Event as WebsocketEvent;

/**
 * Socket.io 事件分发器
 * 由于think-swoole v3.1.0更新了socket.io，
 * 所有socket event集中发射到swoole.websocket.Event，
 * 因此我们需要自行分发事件
 */
class SocketEventDispatcher
{
    protected $websocket;
    protected $fd;
    protected $userTable;
    protected $fdTable;
    protected $throttleTable;
    protected $container;
    protected $config;

    public function __construct(
        Websocket     $websocket,
        UserTable     $userTable,
        ThrottleTable $throttleTable,
        Container     $container,
        Config        $config
    ) {
        $this->websocket     = $websocket;
        $this->fd            = $websocket->getSender();
        $this->userTable     = $userTable;
        $this->throttleTable = $throttleTable;
        $this->container     = $container;
        $this->config        = $config;
    }

    /**
     * 获取当前user
     *
     * @return array|false
     */
    private function getUser()
    {
        return $this->userTable->get($this->fd);
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(WebsocketEvent $event)
    {
        $user = $this->getUser();

        // 检测频率
        if ($user && !$this->throttleTable->try($user['id'])) {
            return $this->websocket->emit($event->type, Result::create(Result::CODE_ACCESS_OVERCLOCK));
        }

        $eventName    = StrUtil::studly($event->type);
        $eventData    = $event->data[0];
        $handlerClass = $this->config->get('swoole.websocket.listen.Event:' . $eventName);

        // 如果没有这个事件处理类
        if (!$handlerClass) {
            return $this->websocket->emit($event->type, Result::create(Result::CODE_PARAM_ERROR));
        }

        /** @var SocketEventHandler */
        $handler = $this->container->make($handlerClass);

        // 数据校验失败
        if (!$handler->verify($eventData ?? [])) {
            return $this->websocket->emit($event->type, Result::create(Result::CODE_PARAM_ERROR));
        }

        Event::trigger('swoole.websocket.Event:' . $eventName,  $eventData);
    }
}
