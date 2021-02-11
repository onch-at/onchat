<?php

declare(strict_types=1);

namespace app\middleware;

use think\Response;
use app\core\Result;
use think\facade\Session;
use app\service\User;

class Auth
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        if (!Session::has(User::SESSION_USER_LOGIN)) {
            return json(new Result(Result::CODE_ERROR_NO_PERMISSION));
        }

        return $next($request);
    }
}
