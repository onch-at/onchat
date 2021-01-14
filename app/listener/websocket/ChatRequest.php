<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use app\core\util\Redis as RedisUtil;
use app\core\service\Chat as ChatService;
use app\core\service\User as UserService;
use app\core\service\Chatroom as ChatroomService;

class ChatRequest extends BaseListener
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        if (!$this->isEstablished()) {
            return false;
        }

        $user = $this->getUser();

        $result = ChatService::request(
            $user['id'],
            $event['chatroomId'],
            $event['reason']
        );

        $this->websocket->emit('chat_request', $result);

        // 如果成功发出申请，则尝试给群主和群管理推送消息
        if ($result->code === Result::CODE_SUCCESS) {
            $userIdList = ChatroomService::getHostAndManagerIdList($event['chatroomId']);

            foreach ($userIdList as $userId) {
                $this->websocket->to(parent::ROOM_CHAT_REQUEST . $userId);
            }

            $this->websocket->emit('chat_request', $result);
        }
    }
}
