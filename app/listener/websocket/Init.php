<?php

declare(strict_types=1);

namespace app\listener\websocket;

use think\facade\Session;
use app\core\handler\User as UserHandler;
use app\model\ChatMember as ChatMemberModel;
use app\core\handler\Friend as FriendHandler;

class Init extends BaseListener
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

        $this->setFdUserPair($event['sessId']);

        $user = $this->getUserByFd();
        $chatrooms = UserHandler::getChatrooms($user['id'])->data;

        // 批量加入所有房间
        foreach ($chatrooms as $chatroom) {
            $this->websocket->join(parent::ROOM_CHATROOM . $chatroom['id']);
        }

        // 加入好友请求房间
        $this->websocket->join(parent::ROOM_FRIEND_REQUEST . $user['id']);

        // 储存uid - fd
        $this->setUserIdFdPair($user['id']);
    }
}
