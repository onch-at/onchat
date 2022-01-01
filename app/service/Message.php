<?php

declare(strict_types=1);

namespace app\service;

use HTMLPurifier;
use HTMLPurifier_Config as HTMLPurifierConfig;
use app\constant\MessageType;
use app\core\Result;
use app\entity\Message as MessageEntity;
use app\entity\RichTextMessage;
use app\entity\TextMessage;
use app\utils\Str as StrUtils;

class Message
{
    /**
     * 净化 $msg['data']
     * 只有WS通道接收的消息需要净化.
     *
     * @param array $msg
     *
     * @return Result
     */
    public function handle(array $msg): Result
    {
        $message = new MessageEntity();

        switch ($msg['type']) {
            case MessageType::TEXT:
                $content = $msg['data']['content'];

                if (StrUtils::isEmpty($content)) {
                    return Result::create(Result::CODE_PARAM_ERROR);
                }

                if (StrUtils::length($content) > ONCHAT_TEXT_MSG_MAX_LENGTH) {
                    return Result::create(Result::CODE_PARAM_ERROR, '文本消息长度过长');
                }

                $message->type = MessageType::TEXT;
                $message->data = new TextMessage($content);
                break;

            case MessageType::RICH_TEXT:
                ['html' => $html, 'text' => $text] = $msg['data'];

                if (StrUtils::isEmpty($text)) {
                    return Result::create(Result::CODE_PARAM_ERROR);
                }

                if (StrUtils::length($text) > ONCHAT_TEXT_MSG_MAX_LENGTH) {
                    return Result::create(Result::CODE_PARAM_ERROR, '文本消息长度过长');
                }

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

                $html = $purifier->purify($html);
                $text = htmlspecialchars($text);

                $message->type = MessageType::RICH_TEXT;
                $message->data = new RichTextMessage($html, $text);
                break;

            default:
                return Result::create(Result::CODE_PARAM_ERROR, '不支持处理该类消息');
        }

        $message->userId     = $msg['userId'];
        $message->chatroomId = $msg['chatroomId'];
        $message->replyId    = isset($msg['replyId']) && is_int($msg['replyId']) ? $msg['replyId'] : null;
        $message->tempId     = $msg['tempId'];

        return Result::success($message);
    }
}
