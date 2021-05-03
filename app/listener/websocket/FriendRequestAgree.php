<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\core\Result;
use app\service\Friend as FriendService;

class FriendRequestAgree extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, FriendService $friendService)
    {
        ['requestId' => $requestId, 'requesterAlias' => $requesterAlias] = $event;

        $user = $this->getUser();

        $result = $friendService->agree($requestId, $user['id'], $requesterAlias);

        $chatroomId = $result->data['chatroomId'];
        $this->websocket->join(parent::ROOM_CHATROOM . $chatroomId);
        $this->websocket->emit(SocketEvent::FRIEND_REQUEST_AGREE, $result);

        // 如果成功同意申请，则尝试给申请人推送消息
        if ($result->code !== Result::CODE_SUCCESS) {
            return false;
        }

        // 拿到申请人的FD
        $requesterFd = $this->fdTable->getFd($result->data['requesterId']);
        if ($requesterFd) {
            // 加入新的聊天室
            $this->websocket->setSender($requesterFd)
                ->join(parent::ROOM_CHATROOM . $chatroomId)
                ->emit(SocketEvent::FRIEND_REQUEST_AGREE, $result);
        }
    }
}
