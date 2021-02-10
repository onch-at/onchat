<?php

declare(strict_types=1);

namespace app\core\mail;

use think\facade\Config;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public static function create(): PHPMailer
    {
        $debug = env('app_debug', false);

        $mail = new PHPMailer($debug);
        $mail->SMTPDebug = $debug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = Config::get('smtp.host');
        $mail->SMTPAuth   = true;
        $mail->Username   = Config::get('smtp.username');
        $mail->Password   = Config::get('smtp.password');
        $mail->SMTPSecure = Config::get('smtp.secure') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = Config::get('smtp.port');

        return $mail;
    }
}
