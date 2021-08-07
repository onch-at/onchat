<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\core\Result;
use app\model\UserInfo as UserInfoModel;
use app\service\User as UserService;
use think\Config;
use think\Cookie;

class Init extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return true;
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(UserService $userService, Cookie $cookie, Config $config)
    {
        $sessId = $cookie->get($config->get('session.name'));

        if (!$sessId) {
            return $this->websocket->emit(SocketEvent::INIT, Result::unauth());
        }

        $this->userTable->set($this->fd, $sessId);

        $userId = $this->getUser()['id'];

        $chatrooms = $userService->getChatrooms($userId);

        // 储存uid - fd
        $this->fdTable->set($userId, $this->fd);

        // 批量加入所有房间
        foreach ($chatrooms as $chatroom) {
            $this->websocket->join(SocketRoomPrefix::CHATROOM . $chatroom->id);
        }

        // 加入好友请求房间
        $this->websocket->join(SocketRoomPrefix::FRIEND_REQUEST . $userId);
        // 加入群聊申请房间
        $this->websocket->join(SocketRoomPrefix::CHAT_REQUEST . $userId);

        $this->websocket->emit(SocketEvent::INIT, Result::success());

        UserInfoModel::update([
            'login_time' => time() * 1000
        ], [
            'user_id' => $userId
        ]);
    }
}
