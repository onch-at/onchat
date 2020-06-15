<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\Container;
use think\facade\Session;
use app\core\handler\User as UserHandler;

class Unload extends BaseListener
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        Session::setId($event['sessId']);
        Session::init();
        $userId = Session::get(UserHandler::SESSION_USER_LOGIN . '.id');
        $chatrooms = UserHandler::getChatrooms($userId)->data;

        foreach ($chatrooms as $chatroom) {
            $this->websocket->leave(parent::ROOM_CHATROOM . $chatroom['id']);
        }
    }
}