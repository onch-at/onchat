<?php

declare(strict_types=1);

namespace app\entity;

use HTMLPurifier;
use HTMLPurifier_Config as HTMLPurifierConfig;

class RichTextMessage
{
    /** HTML */
    public $html;
    /** 文本 */
    public $text;

    public function __construct(string $html, string $text)
    {
        $config = HTMLPurifierConfig::createDefault();
        // 允许的元素
        $config->set('HTML.AllowedElements', [
            'p', 'strong', 'em', 'u', 's', 'blockquote',
            'ol', 'ul', 'li', 'pre', 'br', 'sub', 'sup',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span',
        ]);
        // 允许的属性
        $config->set('HTML.AllowedAttributes', ['class']);
        // 允许的CLASS
        $config->set('Attr.AllowedClasses', [
            'ql-indent-1',
            'ql-indent-2',
            'ql-indent-3',
            'ql-indent-4',
            'ql-indent-5',
            'ql-indent-6',
            'ql-indent-7',
            'ql-indent-8',
            'ql-align-center',
            'ql-align-right',
            'ql-align-justify',
            'ql-font-serif',
            'ql-font-monospace',
            'ql-syntax',
        ]);
        $purifier = new HTMLPurifier($config);

        $this->html = $purifier->purify($html);
        $this->text = htmlspecialchars($text);
    }
}
