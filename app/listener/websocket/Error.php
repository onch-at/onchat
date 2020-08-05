<?php

declare(strict_types=1);

namespace app\listener\websocket;

class Error
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        trace('Error');
    }
}
