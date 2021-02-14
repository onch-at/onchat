<?php

declare(strict_types=1);

namespace app\core\mail;

use think\facade\Config;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer extends PHPMailer
{
    public static function create(): Mailer
    {
        $debug = env('app_debug', false);

        $mail = new Mailer($debug);
        $mail->Charset    = Mailer::CHARSET_UTF8;
        $mail->SMTPDebug  = $debug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = Config::get('smtp.host');
        $mail->SMTPAuth   = true;
        $mail->Username   = Config::get('smtp.username');
        $mail->Password   = Config::get('smtp.password');
        $mail->SMTPSecure = Config::get('smtp.secure') ? Mailer::ENCRYPTION_SMTPS : Mailer::ENCRYPTION_STARTTLS;
        $mail->Port       = Config::get('smtp.port');

        return $mail;
    }

    public function setSubject(string $subject): Mailer
    {
        $this->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        return $this;
    }

    public function setBody(string $body): Mailer
    {
        $this->Body = $body;
        return $this;
    }

    public function setAltBody(?string $altBody): Mailer
    {
        $this->AltBody = $altBody;
        return $this;
    }

    public function setFrom($address, $name = '', $auto = true): Mailer
    {
        parent::setFrom($address, $name, $auto);
        return $this;
    }

    public function isHTML($isHTML = true): Mailer
    {
        parent::isHTML($isHTML);
        return $this;
    }
}
