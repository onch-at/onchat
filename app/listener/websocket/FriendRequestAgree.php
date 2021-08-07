<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\Friend as FriendService;
use think\facade\Validate;
use think\validate\ValidateRule;

class FriendRequestAgree extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'requestId'      => ValidateRule::must()->integer(),
            'requesterAlias' => ValidateRule::has(true),
        ])->check($data);
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(FriendService $friendService, $event)
    {
        ['requestId' => $requestId, 'requesterAlias' => $requesterAlias] = $event;

        $user = $this->getUser();

        $result = $friendService->agree($requestId, $user['id'], $requesterAlias);

        $chatroomId = $result->data['chatroomId'];
        $this->websocket->join(SocketRoomPrefix::CHATROOM . $chatroomId);
        $this->websocket->emit(SocketEvent::FRIEND_REQUEST_AGREE, $result);

        // 如果成功同意申请，则尝试给申请人推送消息
        if ($result->isError()) {
            return false;
        }

        // 拿到申请人的FD
        $requesterFd = $this->fdTable->getFd($result->data['requesterId']);
        if ($requesterFd) {
            // 加入新的聊天室
            $this->websocket->setSender($requesterFd)
                ->join(SocketRoomPrefix::CHATROOM . $chatroomId)
                ->emit(SocketEvent::FRIEND_REQUEST_AGREE, $result);
            $this->websocket->setSender($this->fd);
        }
    }
}
