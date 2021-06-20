<?php

declare(strict_types=1);

namespace app\service;

use app\constant\SessionKey;
use app\core\Result;
use app\job\SendMail as JobSendMail;
use app\util\Str as StrUtil;
use think\Config;
use think\Queue;
use think\Session;

class Index
{
    private $session;
    private $config;
    private $queue;

    public function __construct(Session $session, Config $config, Queue $queue)
    {
        $this->session = $session;
        $this->config  = $config;
        $this->queue   = $queue;
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

        if (StrUtil::length($email) > ONCHAT_EMAIL_MAX_LENGTH) {
            return Result::success(false);
        }

        $data = $this->session->get(SessionKey::EMAIL_CAPTCHA);

        // 如果在60秒内再次发送，则不处理
        if ($data && time() <= $data['time'] + 60) {
            return Result::success(false);
        }

        $captcha = StrUtil::captcha(6);

        $this->session->set(SessionKey::EMAIL_CAPTCHA, [
            'captcha' => password_hash(strtolower($captcha), PASSWORD_DEFAULT),
            'email'   => $email,
            'time'    => time()
        ]);

        $path = resource_path('tpl/mail') . 'captcha.html';

        $result = $this->queue->push(JobSendMail::class, [
            'from'      => [$this->config->get('smtp.username'), 'OnChat'],
            'addresses' => [$email],
            'isHTML'    => true,
            'subject'   => 'OnChat：电子邮箱验证',
            'body'      => StrUtil::assign(file_get_contents($path), ['captcha' => $captcha]),
            'altBody'   => null
        ]);

        return Result::create($result !== false ? Result::CODE_SUCCESS : Result::CODE_ERROR_UNKNOWN);
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
        $data = $this->session->get(SessionKey::EMAIL_CAPTCHA);

        if (!$data) {
            return false;
        }

        [
            'captcha' => $hash,
            'email'   => $mail,
            'time'    => $time
        ] = $data;

        // 验证验证码是否过期，邮箱是否一致，验证码是否正确
        if (time() > $time + 600 || $email !== $mail || !password_verify(strtolower($captcha), $hash)) {
            return false;
        }

        return true;
    }
}
