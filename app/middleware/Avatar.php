<?php

declare(strict_types=1);

namespace app\middleware;

use app\core\Result;
use think\Request;
use think\Response;

/**
 * 头像文件中间件.
 */
class Avatar
{
    /**
     * 处理请求
     *
     * @param Request  $request
     * @param \Closure $next
     * @param int      $size    文件体积
     *
     * @return Response
     */
    public function handle(Request $request, \Closure $next, int $size = 1024 * 1024): Response
    {
        $image = $request->file('image');
        $mine = $image->getMime();

        if (!in_array($mine, ['image/webp', 'image/jpeg', 'image/png'])) {
            return Result::create(Result::CODE_PARAM_ERROR, '文件格式错误，仅接受格式为webp/jpeg/png的图片文件')->toJson();
        }

        if ($image->getSize() > $size) { // 1MB
            return Result::create(Result::CODE_PARAM_ERROR, '文件体积过大，仅接受体积为' . round($size / 1048576, 1) . 'MB以内的文件')->toJson();
        }

        return $next($request);
    }
}
