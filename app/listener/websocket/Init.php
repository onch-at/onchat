<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\contract\SocketEventHandler;
use app\core\Result;
use app\model\UserInfo as UserInfoModel;
use app\service\Token as TokenService;
use app\service\User as UserService;
use think\Request;

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
    public function handle(UserService $userService, TokenService $tokenService, Request $request)
    {
        try {
            $payload = $tokenService->parse($request->param('token'));
        } catch (\Exception $e) {
            $this->websocket->emit(SocketEvent::INIT, Result::unauth($e->getMessage()));
            return $this->websocket->close();
        }

        $userId    = $payload->sub;
        $chatrooms = $userService->getChatrooms($userId);

        $this->userTable->set($this->fd, $payload);

        // 批量加入所有房间
        foreach ($chatrooms as $chatroom) {
            $this->websocket->join(SocketRoomPrefix::CHATROOM . $chatroom->id);
        }

        // 加入用户房间
        $this->websocket->join(SocketRoomPrefix::USER . $userId);

        $this->websocket->emit(SocketEvent::INIT, Result::success());

        UserInfoModel::update([
            'login_time' => time() * 1000
        ], [
            'user_id' => $userId
        ]);
    }
}
