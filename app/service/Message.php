<?php

declare(strict_types=1);

namespace app\service;

use HTMLPurifier;
use HTMLPurifier_Config;
use app\constant\MessageType;
use app\core\Result;
use app\core\storage\Storage;
use app\util\Str as StrUtil;

class Message
{
    /** 消息过长 */
    const CODE_MSG_LONG  = 1;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_MSG_LONG  => '文本消息长度过长',
    ];

    public function handle(array $msg): Result
    {
        switch ($msg['type']) {
            case MessageType::TEXT:
                $content = $msg['data']['content'];

                if (StrUtil::length(StrUtil::trimAll($content)) === 0) {
                    return new Result(Result::CODE_ERROR_PARAM);
                }

                if (StrUtil::length($content) > ONCHAT_TEXT_MSG_MAX_LENGTH) {
                    return new Result(self::CODE_MSG_LONG, self::MSG[self::CODE_MSG_LONG]);
                }

                $data['content'] = htmlspecialchars($content);
                $msg['data'] = $data;
                break;

            case MessageType::RICH_TEXT:
                $html = $msg['data']['html'];
                $text = $msg['data']['text'];

                if (StrUtil::length(StrUtil::trimAll($text)) === 0) {
                    return new Result(Result::CODE_ERROR_PARAM);
                }

                if (StrUtil::length($text) > ONCHAT_TEXT_MSG_MAX_LENGTH) {
                    return new Result(self::CODE_MSG_LONG, self::MSG[self::CODE_MSG_LONG]);
                }

                $config = HTMLPurifier_Config::createDefault();
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

                $data['html'] = $purifier->purify($html);
                $data['text'] = htmlspecialchars($text);

                $msg['data'] = $data;
                break;

            case MessageType::CHAT_INVITATION:
                $data['chatroomId'] = $msg['data']['chatroomId'];
                $msg['data'] = $data;
                break;

            case MessageType::IMAGE:
                $temp = $msg['data'];

                if (!isset($temp['filename']) || !isset($temp['width']) || !isset($temp['height'])) {
                    return new Result(Result::CODE_ERROR_PARAM);
                }

                try {
                    $storage = Storage::getInstance();

                    if (!$storage->exist($msg['data']['filename'])) {
                        return new Result(Result::CODE_ERROR_PARAM, '图片不存在');
                    }

                    $data['filename'] = $temp['filename'];
                    $data['width'] = $temp['width'];
                    $data['height'] = $temp['height'];

                    $msg['data'] = $data;
                } catch (\Exception $e) {
                    return new Result(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
                }
                break;

            case MessageType::VOICE:
                $temp = $msg['data'];

                if (!isset($temp['filename']) || !isset($temp['duration'])) {
                    return new Result(Result::CODE_ERROR_PARAM);
                }

                try {
                    $storage = Storage::getInstance();

                    if (!$storage->exist($msg['data']['filename'])) {
                        return new Result(Result::CODE_ERROR_PARAM, '语音不存在');
                    }

                    $data['filename'] = $temp['filename'];
                    $data['duration'] = $temp['duration'] > 60 ? 60 : $temp['duration'];
                    $data['readedList'] = [];

                    $msg['data'] = $data;
                } catch (\Exception $e) {
                    return new Result(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
                }
                break;

            default:
                return new Result(Result::CODE_ERROR_PARAM, '未知消息类型');
        }

        if (isset($msg['loading'])) {
            unset($msg['loading']);
        }

        return Result::success($msg);
    }
}
