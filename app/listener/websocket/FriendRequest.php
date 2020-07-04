<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\Container;
use think\facade\Session;
use app\core\handler\User as UserHandler;
use app\model\ChatMember as ChatMemberModel;

class FriendRequest extends BaseListener
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

        try {
            $this->websocket->setSender(UserHandler::getWebSocketFileDescriptorByUserId(2))->emit('friend_request', '有人向你申请好友啦！');
        } catch (\Exception $e) {
        }
    }
}
