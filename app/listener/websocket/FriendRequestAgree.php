<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\Container;
use app\core\handler\User as UserHandler;
use app\core\handler\Friend as FriendHandler;
use app\core\Result;

class FriendRequestAgree extends BaseListener
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
        // $username = parent::getUsername();

        $result = FriendHandler::agreeRequest($event['friendRequestId'], $userId);

        $chatroomId = $result->data['chatroomId'];
        $this->websocket->join(parent::ROOM_CHATROOM . $chatroomId);
        $this->websocket->emit('friend_request_agree', $result);

        // 如果成功同意申请，则尝试给申请人推送消息
        if ($result->code === Result::CODE_SUCCESS) {
            $this->websocket->setSender(UserHandler::getWebSocketFileDescriptorByUserId($result->data['selfId']))
                ->join(parent::ROOM_CHATROOM . $chatroomId);
            $this->websocket->setSender(UserHandler::getWebSocketFileDescriptorByUserId($result->data['selfId']))
                ->emit('friend_request_agree', $result);
        }
    }
}
