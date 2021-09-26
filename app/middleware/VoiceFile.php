<?php

declare(strict_types=1);

namespace app\middleware;

use Swoole\StringObject;
use app\core\Result;
use think\Request;
use think\Response;

/**
 * 语音文件中间件
 */
class VoiceFile
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param \Closure $next
     * @param integer $size 文件体积
     * @return Response
     */
    public function handle(Request $request, \Closure $next, int $size = 1024 * 1024): Response
    {

        $voice = $request->file('voice');
        $mine = $voice->getMime();

        if (!stristr($mine, 'video/webm') && !stristr($mine, 'audio/')) {
            return Result::create(Result::CODE_PARAM_ERROR, '文件格式错误，仅接受音频文件')->toJson();
        }

        if ($voice->getSize() > $size) {
            return Result::create(Result::CODE_PARAM_ERROR, '文件体积过大，仅接受体积为' . round($size / 1048576, 1) . 'MB以内的文件')->toJson();
        }

        return $next($request);
    }
}
