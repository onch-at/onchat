<?php

declare(strict_types=1);

namespace app\service;

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
