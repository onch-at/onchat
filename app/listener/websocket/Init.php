<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\core\service\User as UserService;
use app\core\util\Sql as SqlUtil;
use app\model\UserInfo as UserInfoModel;

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
        $chatrooms = UserService::getChatrooms($user['id'])->data;

        // 批量加入所有房间
        foreach ($chatrooms as $chatroom) {
            $this->websocket->join(parent::ROOM_CHATROOM . $chatroom['id']);
        }

        // 加入好友请求房间
        $this->websocket->join(parent::ROOM_FRIEND_REQUEST . $user['id']);
        // 加入群聊申请房间
        $this->websocket->join(parent::ROOM_CHAT_REQUEST . $user['id']);

        // 储存uid - fd
        $this->setUserIdFdPair($user['id']);

        $this->websocket->emit('init');

        UserInfoModel::update([
            'login_time' => SqlUtil::rawTimestamp(),
            'id' => $user['id']
        ]);
    }
}
