<?php

declare(strict_types=1);

namespace app\middleware;

use app\core\Result;
use app\service\Token as TokenService;
use think\Request;
use think\Response;

/**
 * 登录认证中间件.
 */
class Auth
{
    private $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * 处理请求
     *
     * @param Request  $request
     * @param \Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return Result::unauth()->toJson();
        }

        try {
            $payload = $this->tokenService->parse($token);

            if (!$this->tokenService->isAvailable($payload)) {
                return Result::unauth()->toJson();
            }

            return $next($request);
        } catch (\Exception $e) {
            return Result::unauth($e->getMessage())->toJson();
        }
    }
}
