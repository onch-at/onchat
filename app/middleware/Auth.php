<?php

declare(strict_types=1);

namespace app\middleware;

use app\core\Result;
use app\service\User;
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
        if (!Session::has(User::SESSION_USER_LOGIN)) {
            return (new Result(Result::CODE_ERROR_NO_PERMISSION))->toJson();
        }

        return $next($request);
    }
}
