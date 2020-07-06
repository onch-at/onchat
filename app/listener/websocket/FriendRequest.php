<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\Container;
use think\facade\Session;
use app\core\handler\User as UserHandler;
use app\core\handler\Friend as FriendHandler;
use app\core\Result;

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
        parent::initSession();
        $userId = parent::getUserId();
        $username = parent::getUsername();

        $result = FriendHandler::request($userId, $event['userId'], $username);

        $this->websocket->emit('friend_request', $result);

        // 如果成功发出申请，则尝试给被申请人推送消息
        if ($result->code === Result::CODE_SUCCESS) {
            $this->websocket->setSender(UserHandler::getWebSocketFileDescriptorByUserId($event['userId']))
                ->emit('friend_request', $result);
        }
    }
}
