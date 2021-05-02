<?php

declare(strict_types=1);

namespace app\listener\task;

use app\core\mail\Mailer;

class SendMail
{
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

        $mail = Mailer::create()
            ->setFrom(...$from)
            ->isHTML($isHTML)
            ->setSubject($subject)
            ->setBody($body)
            ->setAltBody($altBody);

        foreach ($addresses as $address) {
            $mail->addAddress($address);
        }

        $mail->send();
    }
}
