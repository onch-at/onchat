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

            $time = time();

            // 直接沿用续签令牌，只是修改一下时间
            $payload->iat = $time;
            $payload->nbf = $time;
            $payload->exp = $time + ONCHAT_ACCESS_TOKEN_TTL;
            $payload->ttl = ONCHAT_ACCESS_TOKEN_TTL;

            $tokenExpireTable->set($this->fd, $payload->exp);

            $token = $tokenService->issue($payload);

            return $this->websocket->emit(SocketEvent::REFRESH_TOKEN, Result::success([
                'accessToken' => $token,
                'tokenTime'   => ONCHAT_ACCESS_TOKEN_TTL * 1000
            ]));
        } catch (\Exception $e) {
            return $this->websocket->emit(SocketEvent::REFRESH_TOKEN, Result::create(Result::CODE_AUTH_EXPIRES));
        }
    }
}
