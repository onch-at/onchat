<?php

declare(strict_types=1);

namespace app\listener\task;

use app\core\mail\Mailer;

class SendMail
{
    const TASK_NAME = 'SendMail';

    public function handle($event)
    {
        [
            'from'      => $from,
            'addresses' => $addresses,
            'isHTML'    => $isHTML,
            'subject'   => $subject,
            'body'      => $body,
            'altBody'   => $altBody,
        ] = $event;

        $mail = Mailer::create();

        $mail->setFrom(...$from);

        foreach ($addresses as $address) {
            $mail->addAddress($address);
        }

        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody;

        $mail->send();
    }
}
