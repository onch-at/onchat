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
use app\table\User as UserTable;
use think\Request;
use think\swoole\Websocket;

class Init extends SocketEventHandler
{
    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(Request $request, Websocket $socket, UserService $userService, TokenService $tokenService, UserTable $userTable, ?array $event)
    {
        // TODO: 不再兼容旧版认证方式
        $token = $request->param('token');

        if (!$token) {
            $token = $event['auth'];
        }

        try {
            // $token   = $event['auth'];
            $payload = $tokenService->parse($token);
        } catch (\Exception $e) {
            $socket->emit(SocketEvent::INIT, Result::unauth($e->getMessage()));

            return $socket->close();
        }

        $userId    = $payload->sub;
        $chatrooms = $userService->getChatrooms($userId);

        $userTable->set($socket->getSender(), $payload);

        // 批量加入所有房间
        foreach ($chatrooms as $chatroom) {
            $socket->join(SocketRoomPrefix::CHATROOM . $chatroom->id);
        }

        // 加入用户房间
        $socket->join(SocketRoomPrefix::USER . $userId);

        $socket->emit(SocketEvent::INIT, Result::success());

        UserInfoModel::update([
            'login_time' => time() * 1000,
        ], [
            'user_id' => $userId,
        ]);
    }
}
