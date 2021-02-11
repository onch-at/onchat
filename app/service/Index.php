<?php

declare(strict_types=1);

namespace app\service;

use app\core\Job;
use app\util\Str;
use app\listener\task\SendMail;
use think\swoole\facade\Server;
use app\util\Tpl as TplUtil;

class Index
{

    public function sendMailCaptcha()
    {
        $path = root_path('resource/tpl/mail/') . 'captcha.html';

        Server::task(new Job(SendMail::TASK_NAME, [
            'from'      => ['system@chat.hypergo.net', 'OnChat'],
            'addresses' => ['hyperlife1119@qq.com'],
            'isHTML'    => true,
            'subject'   => 'OnChat：电子邮箱验证',
            'body'      => TplUtil::assign(file_get_contents($path), ['captcha' => Str::captcha(6)]),
            'altBody'   => null
        ]));
    }
}
