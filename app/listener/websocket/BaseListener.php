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

    public function __construct(Websocket $websocket, Server $server)
    {
        $this->server    = $server;
        $this->websocket = $websocket;
        $this->fd        = $websocket->getSender();
    }

    public function getClientIP(): string
    {
        // 通过读取 $request->header['x-real-ip'] 来获取客户端的真实 IP
        return $this->server->getClientInfo($this->fd)['remote_ip'];
    }
}
