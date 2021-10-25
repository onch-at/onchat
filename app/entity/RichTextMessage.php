<?php

declare(strict_types=1);

namespace app\entity;

class RichTextMessage
{
    /** HTML */
    public $html;
    /** 文本 */
    public $text;

    public function __construct(string $html, string $text)
    {
        $this->html = $html;
        $this->text = $text;
    }
}
