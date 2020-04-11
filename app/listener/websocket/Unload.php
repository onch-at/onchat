<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\Container;
use think\facade\Session;
use app\core\handler\User as UserHandler;
use app\model\ChatMember as ChatMemberModel;

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
        $nickname = null;

        foreach ($chatrooms as $chatroom) {
            // 拿到当前用户在这个聊天室的昵称
            $nickname = ChatMemberModel::where('user_id', '=', $userId)->where('chatroom_id', '=', $chatroom['id'])->value('nickname');

            $this->websocket->leave('CHATROOM:' . $chatroom['id']);
            $this->websocket->to('CHATROOM:' . $chatroom['id'])->emit("init", $chatroom['id'] . '[系统消息] 欢送' . $nickname . '离开聊天室！');
        }
    }
}