<?php

declare(strict_types=1);

namespace app\middleware;

use app\core\Result;
use think\Request;
use think\Response;

class ImageFile
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

        $image = $request->file('image');
        $mine = $image->getMime();

        if (!stristr($mine, 'image/')) {
            return (new Result(Result::CODE_ERROR_PARAM, '文件格式错误，仅接受图片文件'))->toJson();
        }

        return $next($request);
    }
}
