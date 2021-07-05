<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\service\Chatroom as ChatroomService;
use think\facade\Validate;
use think\validate\ValidateRule;

class CreateChatroom extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'name'        => ValidateRule::must(),
            'description' => ValidateRule::has(),
        ])->check($data);
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(ChatroomService $chatroomService, $event)
    {
        ['name' => $name, 'description' => $description] = $event;

        $user = $this->getUser();

        $result = $chatroomService->create($name, $description, $user['id'], $user['username']);

        $this->websocket->emit(SocketEvent::CREATE_CHATROOM, $result);

        if ($result->isSuccess()) {
            $this->websocket->join(SocketRoomPrefix::CHATROOM . $result->data['data']['chatroomId']);
        }
    }
}
