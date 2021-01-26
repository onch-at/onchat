<?php

declare(strict_types=1);

namespace app\listener\websocket;

use Swoole\Server;
use think\swoole\Websocket;

/**
 * 基础监听器
 */
abstract class BaseListener
{
    /** Server */
    protected $server;
    /** WebSocket */
    protected $websocket;
    /** 当前用户的FD */
    protected $fd;

    public function __construct(Websocket $websocket)
    {
        $this->server    = app(Server::class);
        $this->websocket = $websocket;
        $this->fd        = $websocket->getSender();
    }

    public function getClientIP(): string
    {
        return $this->server->getClientInfo($this->fd)['remote_ip'];
    }
}
