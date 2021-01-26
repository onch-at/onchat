<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use app\core\util\Redis as RedisUtil;
use app\core\service\User as UserService;
use app\core\service\Friend as FriendService;

class FriendRequestAgree extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        $user = $this->getUser();

        $result = FriendService::agree($event['friendRequestId'], $user['id'], $event['selfAlias']);

        $chatroomId = $result->data['chatroomId'];
        $this->websocket->join(parent::ROOM_CHATROOM . $chatroomId);
        $this->websocket->emit('friend_request_agree', $result);

        // 如果成功同意申请，则尝试给申请人推送消息
        if ($result->code !== Result::CODE_SUCCESS) {
            return false;
        }

        // 拿到申请人的FD
        $selfFd = RedisUtil::getFdByUserId($result->data['selfId']);
        if ($selfFd) {
            // 加入新的聊天室
            $this->websocket->setSender($selfFd)
                ->join(parent::ROOM_CHATROOM . $chatroomId)
                ->emit('friend_request_agree', $result);
        }
    }
}
