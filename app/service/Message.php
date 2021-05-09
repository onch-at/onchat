<?php

declare(strict_types=1);

namespace app\service;

use HTMLPurifier;
use HTMLPurifier_Config as HTMLPurifierConfig;
use app\constant\MessageType;
use app\core\Result;
use app\entity\ChatInvitationMessage;
use app\entity\ImageMessage;
use app\entity\Message as MessageEntity;
use app\entity\RichTextMessage;
use app\entity\TextMessage;
use app\entity\VoiceMessage;
use app\util\Str as StrUtil;

class Message
{
    /** 消息过长 */
    const CODE_MSG_LONG  = 1;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_MSG_LONG  => '文本消息长度过长',
    ];

    /**
     * 净化 $msg['data']
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

                if (StrUtil::length(StrUtil::trimAll($content)) === 0) {
                    return Result::create(Result::CODE_ERROR_PARAM);
                }

                if (StrUtil::length($content) > ONCHAT_TEXT_MSG_MAX_LENGTH) {
                    return Result::create(self::CODE_MSG_LONG, self::MSG[self::CODE_MSG_LONG]);
                }

                $message->type = MessageType::TEXT;
                $message->data = new TextMessage(htmlspecialchars($content));
                break;

            case MessageType::RICH_TEXT:
                ['html' => $html, 'text' => $text] = $msg['data'];

                if (StrUtil::length(StrUtil::trimAll($text)) === 0) {
                    return Result::create(Result::CODE_ERROR_PARAM);
                }

                if (StrUtil::length($text) > ONCHAT_TEXT_MSG_MAX_LENGTH) {
                    return Result::create(self::CODE_MSG_LONG, self::MSG[self::CODE_MSG_LONG]);
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

            case MessageType::IMAGE:
                [
                    'filename' => $filename,
                    'width'    => $width,
                    'height'   => $height,
                ] = $msg['data'];

                $message->type = MessageType::IMAGE;
                $message->data = new ImageMessage($filename, $width, $height);
                break;

            case MessageType::VOICE:
                ['filename' => $filename, 'duration' => $duration] = $msg['data'];

                $message->type = MessageType::VOICE;
                $message->data = new VoiceMessage($filename, $duration);
                break;

            default:
                return Result::create(Result::CODE_ERROR_PARAM, '未知消息类型');
        }

        $message->userId     = $msg['userId'];
        $message->chatroomId = $msg['chatroomId'];
        $message->replyId    = $msg['replyId'] ?? null;
        $message->sendTime   = $msg['sendTime'];

        return Result::success($message);
    }
}
