<?php

declare(strict_types=1);

namespace app\service;

use HTMLPurifier;
use HTMLPurifier_Config as HTMLPurifierConfig;
use app\constant\MessageType;
use app\core\Result;
use app\entity\ChatInvitationMessage;
use app\entity\Message as MessageEntity;
use app\entity\RichTextMessage;
use app\entity\TextMessage;
use app\util\Str as StrUtil;

class Message
{
    /**
     * 净化 $msg['data']
     * 只有WS通道接收的消息需要净化
     *
     * @param array $msg
     * @return Result
     */
    public function handle(array $msg): Result
    {
        $message = new MessageEntity();

        switch ($msg['type']) {
            case MessageType::TEXT:
                $content = $msg['data']['content'];

                if (StrUtil::isEmpty($content)) {
                    return Result::create(Result::CODE_ERROR_PARAM);
                }

                if (StrUtil::length($content) > ONCHAT_TEXT_MSG_MAX_LENGTH) {
                    return Result::create(Result::CODE_ERROR_PARAM, '文本消息长度过长');
                }

                $message->type = MessageType::TEXT;
                $message->data = new TextMessage(htmlspecialchars($content));
                break;

            case MessageType::RICH_TEXT:
                ['html' => $html, 'text' => $text] = $msg['data'];

                if (StrUtil::isEmpty($text)) {
                    return Result::create(Result::CODE_ERROR_PARAM);
                }

                if (StrUtil::length($text) > ONCHAT_TEXT_MSG_MAX_LENGTH) {
                    return Result::create(Result::CODE_ERROR_PARAM, '文本消息长度过长');
                }

                $config = HTMLPurifierConfig::createDefault();
                // 允许的元素
                $config->set('HTML.AllowedElements', [
                    'p', 'strong', 'em', 'u', 's', 'blockquote',
                    'ol', 'ul', 'li', 'pre', 'br', 'sub', 'sup',
                    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span'
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

            case MessageType::CHAT_INVITATION:
                $chatroomId = $msg['data']['chatroomId'];

                $message->type = MessageType::CHAT_INVITATION;
                $message->data = new ChatInvitationMessage($chatroomId);
                break;

            default:
                return Result::create(Result::CODE_ERROR_PARAM, '不支持处理该类消息');
        }

        $message->userId     = $msg['userId'];
        $message->chatroomId = $msg['chatroomId'];
        $message->replyId    = $msg['replyId'] ?? null;
        $message->sendTime   = $msg['sendTime'];

        return Result::success($message);
    }
}
