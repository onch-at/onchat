<?php

declare(strict_types=1);

namespace app\contract;

use app\table\Fd as FdTable;
use app\table\Throttle as ThrottleTable;
use app\table\User as UserTable;
use think\swoole\Websocket;

/**
 * Socket 事件处理程序
 */
abstract class SocketEventHandler
{
    protected $websocket;
    protected $fd;
    protected $userTable;
    protected $fdTable;
    protected $throttleTable;

    public function __construct(
        Websocket     $websocket,
        UserTable     $userTable,
        FdTable       $fdTable,
        ThrottleTable $throttleTable
    ) {
        $this->websocket     = $websocket;
        $this->fd            = $websocket->getSender();
        $this->userTable     = $userTable;
        $this->fdTable       = $fdTable;
        $this->throttleTable = $throttleTable;
    }

    /**
     * 获取当前user
     *
     * @return array|false
     */
    protected function getUser()
    {
        return $this->userTable->get($this->fd);
    }

    /**
     * 验证 event data
     *
     * @param array $data
     * @return boolean
     */
    abstract public function verify(array $data): bool;
}
