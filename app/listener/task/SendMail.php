<?php

declare(strict_types=1);

namespace app\listener\task;

use app\core\mail\Mailer;

class SendMail
{
    /** 发送方：[address, name] */
    private $from;
    /** 接收方：[address, ...] */
    private $addresses;
    /** 正文是否为HTML */
    private $isHTML;
    /** 邮件主题 */
    private $subject;
    /** 邮件正文 */
    private $body;
    /** 邮件替代正文 */
    private $altBody;

    public function __construct(
        array $from,
        array $addresses,
        bool $isHTML,
        string $subject,
        string $body,
        ?string $altBody = null
    ) {
        $this->from = $from;
        $this->addresses = $addresses;
        $this->isHTML = $isHTML;
        $this->subject = $subject;
        $this->body = $body;
        $this->altBody = $altBody;
    }

    public function handle()
    {
        $mail = Mailer::create();

        $mail->setFrom(...$this->from);

        foreach ($this->addresses as $address) {
            $mail->addAddress($address);
        }

        $mail->isHTML($this->isHTML);
        $mail->Subject = $this->subject;
        $mail->Body    = $this->body;
        $mail->AltBody = $this->altBody;

        $mail->send();
    }
}
