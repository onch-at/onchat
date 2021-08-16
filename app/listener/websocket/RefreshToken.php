<?php

declare(strict_types=1);

namespace app\listener\websocket;

use app\constant\SocketEvent;
use app\contract\SocketEventHandler;
use app\core\Result;
use app\service\Token as TokenService;
use app\table\TokenExpire as TokenExpireTable;
use think\facade\Validate;
use think\validate\ValidateRule;

class RefreshToken extends SocketEventHandler
{

    public function verify(array $data): bool
    {
        return Validate::rule([
            'refreshToken' => ValidateRule::must()
        ])->check($data);
    }

    public function handle(TokenExpireTable $tokenExpireTable, TokenService $tokenService, array $event)
    {
        ['refreshToken' => $jwt] = $event;

        try {
            $payload = $tokenService->parse($jwt);

            if (!$tokenService->isAvailable($payload)) {
                return $this->websocket->emit(SocketEvent::REFRESH_TOKEN, Result::create(Result::CODE_AUTH_EXPIRES));
            }

            $payload = $tokenService->refresh($payload);
            $token   = $tokenService->issue($payload);

            $tokenExpireTable->set($this->fd, $payload->exp);

            return $this->websocket->emit(SocketEvent::REFRESH_TOKEN, Result::success($token));
        } catch (\Exception $e) {
            return $this->websocket->emit(SocketEvent::REFRESH_TOKEN, Result::create(Result::CODE_AUTH_EXPIRES));
        }
    }
}
