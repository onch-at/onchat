<?php

declare(strict_types=1);

namespace app\entity;

use app\utils\Str as StrUtils;

class TextMessage
{
    /** 内容 */
    public $content;
    /** 是否全为表情符号 */
    public $emoji;

    public function __construct(string $content)
    {
        $this->content = htmlspecialchars($content);
        $this->emoji   = StrUtils::length(preg_replace(EMOJI_PATTERN, '', $content)) === 0;
    }
}
