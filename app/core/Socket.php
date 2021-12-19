<?php

declare(strict_types=1);

namespace app\core;

use think\Container;
use think\swoole\Websocket;

class Socket
{
  /**
   * get WebSocket instance
   *
   * @return Websocket
   */
  public static function getInstance(): Websocket
  {
    return Container::getInstance()->make(Websocket::class);
  }
}
