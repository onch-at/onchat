<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\MessageType;
use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\core\Result;
use app\service\Chatroom as ChatroomService;

class Message extends SocketEventHandler
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event, ChatroomService $chatroomService)
    {
        ['msg' => $msg] = $event;

        // 语音，图片消息只能通过HTTP API来上传并发送
        if (in_array($msg['type'], [MessageType::VOICE, MessageType::IMAGE])) {
            return $this->websocket
                ->emit(SocketEvent::MESSAGE, Result::create(Result::CODE_ERROR_PARAM, '该类型的消息不允许通过WS通道发送'));
        }

        $msg['userId'] = $this->getUser()['id'];
        $result = $chatroomService->addMessage($msg);

        if (!$result->isSuccess()) {
            return $this->websocket->emit(SocketEvent::MESSAGE, $result);
        }

        // TODO 群聊的头像
        $this->websocket
            ->to(SocketRoomPrefix::CHATROOM . $msg['chatroomId'])
            ->emit(SocketEvent::MESSAGE, $result);
    }
}
