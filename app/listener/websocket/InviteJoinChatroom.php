<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\Result;
use app\core\service\ChatInvitation;
use app\core\service\User as UserService;
use app\core\service\Chatroom as ChatroomService;

class InviteJoinChatroom extends BaseListener
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

        $user = $this->getUserByFd();

        $result = ChatInvitation::invite($user['id'], $event['chatroomId'], $event['chatroomIdList']);

        // 注意这边，邀请人收到的result.data是array类型
        $this->websocket->emit('invite_join_chatroom', $result);

        if ($result->code !== Result::CODE_SUCCESS) {
            return;
        }

        // 给每个受邀者发消息
        foreach ($result->data as $item) {
            $this->websocket
                ->to(self::ROOM_CHAR_INVITATION . $item['inviteeId'])
                ->emit('invite_join_chatroom', Result::success($item));
        }
    }
}
