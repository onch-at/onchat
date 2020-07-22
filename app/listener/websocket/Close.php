<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\facade\Session;
use app\core\handler\User as UserHandler;

class Close extends BaseListener
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        $fd = $this->websocket->getSender();
        $sessId = UserHandler::getSessIdBytWebSocketFileDescriptor($fd);
        if ($sessId) {
            Session::setId($sessId);
            Session::init();

            $userId = Session::get(UserHandler::SESSION_USER_LOGIN . '.id');
            $userId && UserHandler::removeUserIdWebSocketFileDescriptorPair($userId);
        }

        UserHandler::removeWebSocketFileDescriptorSessIdPair($fd);
    }
}
