<?php

namespace app\websocket;

use Swoole\Server;
use Swoole\Websocket\Frame;
use think\Config;
use think\Request;
use think\swoole\websocket\socketio\Handler as BaseHandler;

/**
 * 自定义的WebSocket处理器
 */
class Handler extends BaseHandler
{
    public function __construct(Server $server, Config $config)
    {
        parent::__construct($server, $config);
    }

    /**
     * 连接打通时
     *
     * @param int     $fd
     * @param Request $request
     */
    public function onOpen($fd, Request $request)
    {
        parent::onOpen($fd, $request);
    }

    /**
     * 发生通讯时
     * 仅在未找到事件处理程序时触发
     *
     * @param Frame $frame
     * @return bool
     */
    public function onMessage(Frame $frame)
    {
        return parent::onMessage($frame);
    }

    /**
     * 连接断开时
     *
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose($fd, $reactorId)
    {
        return;
    }
}
