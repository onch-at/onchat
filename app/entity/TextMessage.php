<?php

declare(strict_types=1);

namespace app\entity;

class TextMessage
{
    /** 内容 */
    public $content;
    /** 是否全为表情符号 */
    public $emoji;

    public function __construct(string $content, ?bool $emoji = null)
    {
        $this->content = $content;
        $this->emoji = $emoji;
    }
}
