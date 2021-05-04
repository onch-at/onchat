<?php

declare(strict_types=1);

namespace app\middleware;

use app\constant\SessionKey;
use app\core\Result;
use think\Request;
use think\Response;
use think\facade\Session;

class Auth
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if (!Session::has(SessionKey::USER_LOGIN)) {
            return Result::create(Result::CODE_ERROR_NO_PERMISSION)->toJson();
        }

        return $next($request);
    }
}
