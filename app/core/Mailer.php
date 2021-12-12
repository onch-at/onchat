<?php

declare(strict_types=1);

namespace app\core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use think\facade\Config;

/**
 * 对 PHPMailer 的进一步封装，支持链式调用.
 */
class Mailer extends PHPMailer
{
    public static function create(): self
    {
        $mailer = new self(env('app_debug', false));
        $mailer->isSMTP();
        $mailer->CharSet    = self::CHARSET_UTF8;
        $mailer->Encoding   = self::ENCODING_BASE64;
        $mailer->Host       = Config::get('smtp.host');
        $mailer->Username   = Config::get('smtp.username');
        $mailer->Password   = Config::get('smtp.password');
        $mailer->Port       = Config::get('smtp.port');
        $mailer->SMTPSecure = Config::get('smtp.secure') ? self::ENCRYPTION_SMTPS : self::ENCRYPTION_STARTTLS;
        $mailer->SMTPDebug  = $mailer->exceptions ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        $mailer->SMTPAuth   = true;

        return $mailer;
    }

    public function setSubject(string $subject): self
    {
        $this->Subject = $subject;

        return $this;
    }

    public function setBody(string $body): self
    {
        $this->Body = $body;

        return $this;
    }

    public function setAltBody(?string $altBody): self
    {
        $this->AltBody = $altBody;

        return $this;
    }

    public function setFrom($address, $name = '', $auto = true): self
    {
        parent::setFrom($address, $name, $auto);

        return $this;
    }

    public function isHTML($isHTML = true): self
    {
        parent::isHTML($isHTML);

        return $this;
    }
}
