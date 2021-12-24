<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\ChatRecord as ChatRecordService;
use think\facade\Validate;
use think\swoole\Websocket;
use think\validate\ValidateRule;

class RevokeMessage extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'chatroomId' => ValidateRule::must()->integer(),
            'id'         => ValidateRule::must()->integer(),
        ])->check($data);
    }

    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(Websocket $socket, ChatRecordService $chatRecordService, array $event)
    {
        ['chatroomId' => $chatroomId, 'id' => $id] = $event;

        $user   = $this->getUser();
        $result = $chatRecordService->revokeRecord($id, $user['id'], $chatroomId);

        $socket->to(SocketRoomPrefix::CHATROOM . $chatroomId)
            ->emit(SocketEvent::REVOKE_MESSAGE, $result);
    }
}
