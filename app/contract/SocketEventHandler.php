<?php

declare(strict_types=1);

namespace app\contract;

use app\table\Throttle as ThrottleTable;
use app\table\User as UserTable;
use think\swoole\Websocket;
use think\swoole\websocket\Room;

/**
 * Socket 事件处理程序.
 */
abstract class SocketEventHandler
{
    protected $room;
    protected $userTable;
    protected $throttleTable;

    public function __construct(
        Room $room,
        UserTable $userTable,
        ThrottleTable $throttleTable
    ) {
        $this->room          = $room;
        $this->userTable     = $userTable;
        $this->throttleTable = $throttleTable;
    }

    /**
     * 获取当前user.
     *
     * @param Websocket $socket
     *
     * @return array|false
     */
    protected function getUser(Websocket $socket)
    {
        return $this->userTable->get($socket->getSender());
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
