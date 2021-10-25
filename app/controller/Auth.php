<?php

declare(strict_types=1);

namespace app\controller;

use app\core\Result;
use app\middleware\Jsonify;
use app\service\Auth as AuthService;

class Auth
{
    protected $service;

    protected $middleware = [Jsonify::class];

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    /**
     * 通过续签令牌来刷新访问令牌.
     *
     * @param string $token
     *
     * @return Result
     */
    public function refresh(string $token): Result
    {
        return $this->service->refresh($token);
    }

    /**
     * 获取令牌主人信息.
     *
     * @return Result
     */
    public function info(): Result
    {
        return $this->service->info();
    }

    /**
     * 退出认证，废弃令牌.
     *
     * @return void
     */
    public function logout()
    {
        return $this->service->logout();
    }
}
