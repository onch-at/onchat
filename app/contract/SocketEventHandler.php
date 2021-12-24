<?php

declare(strict_types=1);

namespace app\contract;

use app\table\User as UserTable;
use think\Container;
use think\swoole\Websocket;

/**
 * Socket 事件处理程序.
 */
abstract class SocketEventHandler
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * 获取当前user.
     *
     * @return array|false
     */
    protected function getUser()
    {
        return $this->container->invoke(function (Websocket $socket, UserTable $table) {
            return $table->get($socket->getSender());
        });
    }

    /**
     * 验证 event data.
     *
     * @param array $data
     *
     * @return bool
     */
    public function verify(array $data): bool
    {
        return true;
    }
}
