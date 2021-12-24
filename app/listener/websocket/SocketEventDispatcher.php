<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\contract\SocketEventHandler;
use app\core\Result;
use app\table\Throttle as ThrottleTable;
use app\table\User as UserTable;
use app\utils\Str as StrUtils;
use think\Config;
use think\Container;
use think\facade\Event;
use think\swoole\Websocket;
use think\swoole\websocket\Event as WebsocketEvent;

/**
 * Socket.io 事件分发器
 * 由于think-swoole v3.1.0更新了socket.io，
 * 所有socket event集中发射到swoole.websocket.Event，
 * 因此我们需要自行分发事件.
 */
class SocketEventDispatcher
{
    protected $container;
    protected $config;

    public function __construct(Config $config, Container $container)
    {
        $this->config    = $config;
        $this->container = $container;
    }

    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(UserTable $userTable, ThrottleTable $throttleTable, Websocket $socket, WebsocketEvent $event)
    {
        $user = $userTable->get($socket->getSender());

        if ($user) {
            // 检测频率，排除 RTC 传输数据的情况
            if ($event->type !== SocketEvent::RTC_DATA && !$throttleTable->try($user['id'])) {
                return $socket->emit($event->type, Result::create(Result::CODE_ACCESS_OVERCLOCK));
            }

            // do something...
        }

        $eventName    = StrUtils::studly($event->type);
        $eventData    = $event->data[0];
        $handlerClass = $this->config->get('swoole.websocket.listen.Event:' . $eventName);

        // 如果没有这个事件处理类
        if (!$handlerClass) {
            return $socket->emit($event->type, Result::create(Result::CODE_PARAM_ERROR));
        }

        /** @var SocketEventHandler */
        $handler = $this->container->make($handlerClass);

        // 数据校验失败
        if (!$handler->verify($eventData ?? [])) {
            return $socket->emit($event->type, Result::create(Result::CODE_PARAM_ERROR));
        }

        Event::trigger('swoole.websocket.Event:' . $eventName, $eventData);
    }
}
