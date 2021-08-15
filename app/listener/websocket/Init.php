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
use app\table\TokenExpire as TokenExpireTable;
use think\facade\Validate;
use think\validate\ValidateRule;

class Init extends SocketEventHandler
{
    public function verify(array $data): bool
    {
        return Validate::rule([
            'accessToken' => ValidateRule::must(),
        ])->check($data);
    }

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(UserService $userService, TokenService $tokenService, TokenExpireTable $tokenExpireTable, array $event)
    {
        ['accessToken' => $token] = $event;

        if (!$token) {
            return $this->websocket->emit(SocketEvent::INIT, Result::unauth());
        }

        try {
            $payload = $tokenService->parse($token);
            $this->userTable->set($this->fd, $payload);
        } catch (\Exception $e) {
            return $this->websocket->emit(SocketEvent::INIT, Result::unauth($e->getMessage()));
        }

        $userId    = $payload->sub;
        $tokenTime = $payload->exp - time(); // token 有效期
        $chatrooms = $userService->getChatrooms($userId);

        $tokenExpireTable->set($this->fd, $payload->exp);

        // 批量加入所有房间
        foreach ($chatrooms as $chatroom) {
            $this->websocket->join(SocketRoomPrefix::CHATROOM . $chatroom->id);
        }

        // 加入用户房间
        $this->websocket->join(SocketRoomPrefix::USER . $userId);

        $this->websocket->emit(SocketEvent::INIT, Result::success([
            'tokenTime' => $tokenTime * 1000,
        ]));

        UserInfoModel::update([
            'login_time' => time() * 1000
        ], [
            'user_id' => $userId
        ]);
    }
}
