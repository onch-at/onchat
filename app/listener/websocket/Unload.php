<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\handler\User as UserHandler;

class Unload extends BaseListener
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        $user = $this->getUserByFd();
        $this->removeFdUserPair();
        $this->removeUserIdFdPair($user['id']);
    }
}
