<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\Chatroom as ChatroomService;
use think\facade\Validate;
use think\swoole\Websocket;
use think\validate\ValidateRule;

class CreateChatroom extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'name'        => ValidateRule::must(),
            'description' => ValidateRule::has(true),
        ])->check($data);
    }

    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(Websocket $socket, ChatroomService $chatroomService, array $event)
    {
        ['name' => $name, 'description' => $description] = $event;

        $user = $this->getUser($socket);

        $result = $chatroomService->create($name, $description, $user['id'], $user['username']);

        $socket->emit(SocketEvent::CREATE_CHATROOM, $result);

        if ($result->isSuccess()) {
            $socket->join(SocketRoomPrefix::CHATROOM . $result->data['data']['chatroomId']);
        }
    }
}
