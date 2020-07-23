<?php

declare(strict_types=1);

namespace app\listener\websocket;

class Close extends BaseListener
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        $user = $this->getUser();
        $this->removeUser();
        $this->removeUserIdFdPair((int) $user->id);
    }
}
