<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\Container;
use think\facade\Session;
use app\core\handler\User as UserHandler;
use app\core\handler\Chatroom as ChatroomHandler;

class Message extends BaseListener
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
        $msg = $event['msg'];
        // ChatroomHandler::setMessage($userId, $event['msg']);
        $this->websocket->to('CHATROOM:' . $msg['chatroomId'])->emit("message",  '[RID-' . $msg['chatroomId'] . '] UID-' . $userId . ': ' . $msg['content']);
        $this->websocket->to('CHATROOM:' . $msg['chatroomId'])->emit("message", ChatroomHandler::setMessage($userId, $event['msg']));
    }
}