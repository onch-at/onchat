<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\MessageType;
use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\core\Result;
use app\service\ChatRecord as ChatRecordService;
use app\service\Message as MessageService;
use think\facade\Validate;
use think\validate\ValidateRule;

class Message extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'chatroomId' => ValidateRule::must()->integer(),
            'data'       => ValidateRule::must()->array(),
            'tempId'     => ValidateRule::must(),
            'type'       => ValidateRule::must()->integer(),
            'userId'     => ValidateRule::must()->integer(),
        ])->check($data);
    }

    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(ChatRecordService $chatRecordService, MessageService $messageService, array $event)
    {
        // 语音，图片消息等只能通过HTTP API来上传并发送
        if (in_array($event['type'], [MessageType::VOICE, MessageType::IMAGE, MessageType::TIPS])) {
            $result = Result::create(Result::CODE_PARAM_ERROR, '该类型的消息不允许通过WS通道发送');

            return $this->websocket->emit(SocketEvent::MESSAGE, $result);
        }

        $event['userId'] = $this->getUser()['id'];
        $result = $messageService->handle($event);

        if ($result->isError()) {
            return $this->websocket->emit(SocketEvent::MESSAGE, $result);
        }

        $result = $chatRecordService->addRecord($result->data);

        if ($result->isError()) {
            return $this->websocket->emit(SocketEvent::MESSAGE, $result);
        }

        // TODO 群聊的头像
        $this->websocket
            ->to(SocketRoomPrefix::CHATROOM.$event['chatroomId'])
            ->emit(SocketEvent::MESSAGE, $result);
    }
}
