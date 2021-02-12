<?php

declare(strict_types=1);

namespace app\service;

use app\core\Job;
use app\util\Str;
use think\Config;
use think\Session;
use app\core\Result;
use app\util\Tpl as TplUtil;
use app\listener\task\SendMail;
use think\swoole\facade\Server;

class Index
{
    const SESSION_EMAIL_CAPTCHA = 'email_captcha';

    private $session;
    private $config;

    public function __construct(Session $session, Config $config)
    {
        $this->session = $session;
        $this->config  = $config;
    }

    /**
     * 发送邮箱验证码
     * 验证码10分钟内有效，1分钟内不允许重复发送
     *
     * @param string $email
     * @return Result
     */
    public function sendEmailCaptcha(string $email): Result
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Result::success(false);
        }

        $data = $this->session->get(self::SESSION_EMAIL_CAPTCHA);

        // 如果在60秒内再次发送，则不处理
        if ($data && time() <= $data['time'] + 60) {
            return Result::success(false);
        }

        $captcha = Str::captcha(6);

        $this->session->set(self::SESSION_EMAIL_CAPTCHA, [
            'captcha' => password_hash($captcha, PASSWORD_DEFAULT),
            'email'   => $email,
            'time'    => time()
        ]);

        $path = root_path('resource/tpl/mail/') . 'captcha.html';

        $result = Server::task(new Job(SendMail::TASK_NAME, [
            'from'      => [$this->config->get('smtp.username'), 'OnChat'],
            'addresses' => [$email],
            'isHTML'    => true,
            'subject'   => 'OnChat：电子邮箱验证',
            'body'      => TplUtil::assign(file_get_contents($path), ['captcha' => $captcha]),
            'altBody'   => null
        ]));

        return Result::success($result !== false);
    }

    /**
     * 验证邮箱验证码是否正确
     *
     * @param string $email
     * @param string $captcha
     * @return boolean
     */
    public function checkEmailCaptcha(string $email, string $captcha): bool
    {
        $data = $this->session->get(self::SESSION_EMAIL_CAPTCHA);

        if (!$data) {
            return false;
        }

        [
            'captcha' => $hash,
            'email'   => $mail,
            'time'    => $time
        ] = $data;

        // 验证验证码是否过期，邮箱是否一致，验证码是否正确
        if (time() > $time + 600 || $email !== $mail || !password_verify($captcha, $hash)) {
            return false;
        }

        return true;
    }
}
