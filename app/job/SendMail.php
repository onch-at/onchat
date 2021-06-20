<?php

declare(strict_types=1);

namespace app\job;

use app\core\mail\Mailer;
use think\queue\Job;

class SendMail
{
    public function fire(Job $job, $data)
    {
        [
            'from'      => $from,
            'addresses' => $addresses,
            'isHTML'    => $isHTML,
            'subject'   => $subject,
            'body'      => $body,
            'altBody'   => $altBody,
        ] = $data;

        $mailer = Mailer::create()
            ->setFrom(...$from)
            ->isHTML($isHTML)
            ->setSubject($subject)
            ->setBody($body)
            ->setAltBody($altBody);

        foreach ($addresses as $address) {
            $mailer->addAddress($address);
        }

        // 如果发送成功 或 重试达到3次
        if ($mailer->send() || $job->attempts() > 3) {
            $job->delete();
        }
    }
}
