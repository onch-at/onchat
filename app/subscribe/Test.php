<?php
declare (strict_types = 1);

namespace app\subscribe;

use think\Container;
use think\swoole\Websocket;
use think\facade\Session;
use app\core\handler\User as UserHandler;
use app\model\ChatMember as ChatMemberModel;

class Test
{
    public $websocket;

    /**
     * 注入容器管理类，从容器中取出Websocket类，或者也可以直接注入Websocket类
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->websocket = $container->make(Websocket::class);
    }

    // public function onInit($event) {
    //     Session::setId($event['sessId']);
    //     Session::init();
    //     $userId = Session::get(UserHandler::SESSION_USER_LOGIN . '.id');
    //     $chatrooms = UserHandler::getChatrooms($userId)->data;
    //     $nickname = null;

    //     foreach ($chatrooms as $chatroom) {
    //         // 拿到当前用户在这个聊天室的昵称
    //         $nickname = ChatMemberModel::where('user_id', '=', $userId)->where('chatroom_id', '=', $chatroom['id'])->value('nickname');

    //         $this->websocket->join('CHATROOM:' . $chatroom['id']);
    //         $this->websocket->to('CHATROOM:' . $chatroom['id'])->emit("init", $chatroom['id'] . '[系统消息] 111欢迎' . $nickname . '加入聊天室！');
    //     }
    // }

    public function onMessage($event) {
        Session::setId($event['sessId']);
        Session::init();
        $userId = Session::get(UserHandler::SESSION_USER_LOGIN . '.id');
        $this->websocket->to('CHATROOM:' . $event['msg']['chatroomId'])->emit("message",  '[RID-' . $event['msg']['chatroomId'] . '] UID-' . $userId . ': ' . $event['msg']['content']);
    }
}
