<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\model\User;

class Test
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        User::find(1);
    }
}
