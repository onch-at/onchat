<?php

declare(strict_types=1);

namespace app\contract;

use app\table\Throttle as ThrottleTable;
use app\table\User as UserTable;
use think\swoole\Websocket;
use think\swoole\websocket\Room;

/**
 * Socket 事件处理程序
 */
abstract class SocketEventHandler
{
    protected $websocket;
    protected $room;
    protected $fd;
    protected $userTable;
    protected $throttleTable;

    public function __construct(
        Websocket     $websocket,
        Room          $room,
        UserTable     $userTable,
        ThrottleTable $throttleTable
    ) {
        $this->websocket     = $websocket;
        $this->room          = $room;
        $this->fd            = $websocket->getSender();
        $this->userTable     = $userTable;
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
