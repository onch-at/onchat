<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\Friend as FriendService;
use think\facade\Validate;
use think\swoole\Websocket;
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
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(Websocket $socket, FriendService $friendService, $event)
    {
        ['requestId' => $requestId, 'requesterAlias' => $requesterAlias] = $event;

        $user = $this->getUser($socket);

        $result = $friendService->agree($requestId, $user['id'], $requesterAlias);

        $chatroomId = $result->data['chatroomId'];
        $socket->join(SocketRoomPrefix::CHATROOM . $chatroomId);
        $socket->emit(SocketEvent::FRIEND_REQUEST_AGREE, $result);

        // 如果成功同意申请，则尝试给申请人推送消息
        if ($result->isFail()) {
            return false;
        }

        // 拿到申请人的FD
        $requestId    = $result->data['requesterId'];
        $requesterFds = $this->room->getClients(SocketRoomPrefix::USER . $requestId);

        // 加入新的聊天室
        foreach ($requesterFds as $fd) {
            $this->room->add($fd, SocketRoomPrefix::CHATROOM . $chatroomId);
        }

        $socket->to(SocketRoomPrefix::USER . $requestId)
            ->emit(SocketEvent::CHAT_REQUEST_AGREE, $result);
    }
}
