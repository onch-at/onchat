<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\ChatMember;
use app\model\User;
use app\model\UserInfo;
use app\model\ChatRecord;
use app\model\Chatroom;
use think\captcha\facade\Captcha;
use think\facade\Db;
use think\Response;
use app\core\util\Sql as SqlUtil;

use app\core\handler\Chatroom as ChatroomHandler;
use app\core\handler\User as UserHandler;
use app\core\handler\Friend as FriendHandler;
use app\model\FriendRequest;
use app\core\identicon\generator\ImageMagickGenerator;
use app\core\oss\Client;
use Identicon\Generator\SvgGenerator;
use think\facade\Cache;
use OSS\OssClient;
use OSS\Core\OssException;
use app\core\util\Date as DateUtil;
use HTMLPurifier;
use HTMLPurifier_Config;

class Index extends BaseController
{
    public function addChatroom($name)
    {
        Db::transaction(function () use ($name) {
            $timestamp = SqlUtil::rawTimestamp();
            // 创建一个聊天室
            $chatroom = Chatroom::create([
                'name'        => $name,
                'type'        => 0,
                'create_time' => $timestamp,
                'update_time' => $timestamp,
            ]);

            // Db::execute("
            //     CREATE TABLE IF NOT EXISTS chat_record_" . $chatroom->id . " (
            //         id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            //         chatroom_id INT UNSIGNED NOT NULL          COMMENT '聊天室ID',
            //         user_id     INT UNSIGNED NULL              COMMENT '消息发送者ID',
            //         type        TINYINT(1) UNSIGNED NOT NULL   COMMENT '消息类型',
            //         data        JSON NOT NULL                  COMMENT '消息数据体',
            //         reply_id    INT UNSIGNED NULL              COMMENT '回复消息的消息记录ID',
            //         create_time BIGINT UNSIGNED NOT NULL,
            //         FOREIGN KEY (chatroom_id) REFERENCES chatroom(id) ON DELETE CASCADE ON UPDATE CASCADE,
            //         FOREIGN KEY (user_id)     REFERENCES user(id)     ON DELETE CASCADE ON UPDATE CASCADE
            //     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            // ");

            // 添加聊天成员
            User::find(1)->chatrooms()->attach($chatroom->id, [
                'role' => 0,
                'nickname' => 'HyperLife1119',
                'create_time' => $timestamp,
                'update_time' => $timestamp,
            ]);

            // // 添加聊天成员
            // User::find(2)->chatrooms()->attach($chatroom->id, [
            //     'role' => 0,
            //     'nickname' => '12345'
            // ]);
        });
    }


    public function index()
    {
        $html = '<p>44444</p>';
        $config = HTMLPurifier_Config::createDefault();
        // 允许的元素
        $config->set('HTML.AllowedElements', [
            'p', 'strong', 'em', 'u', 's', 'blockquote',
            'ol', 'ul', 'li', 'pre', 'br', 'sub', 'sub',
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
        $clean_html = $purifier->purify($html);
        dump($html);
        dump($clean_html);
        // return response($content, 200, ['Content-Length' => strlen($content)])->contentType('image/png');



        // $identicon = new \Identicon\Identicon(new ImageMagickGenerator());

        // // 存储空间名称
        // $bucket = "onchat";
        // $ossClient = Client::getInstance();
        // $user = User::select()->toArray();
        // foreach ($user as $item) {
        //     $object = Client::getRootPath() . 'avatar/user/' . $item['id'] . '/' . md5((string) DateUtil::now()) . '.png';
        //     $content = $identicon->getImageData($item['id'], 256, null, '#f0f0f0');
        //     try {
        //         $ossClient->putObject($bucket, $object, $content);
        //         UserInfo::update([
        //             'avatar' => $object,
        //         ], ['id' => $item['id']]);
        //     } catch (OssException $e) {
        //         printf(__FUNCTION__ . ": FAILED\n");
        //         printf($e->getMessage() . "\n");
        //     }
        // }

        // return 'okk';
    }

    /**
     * 验证码
     *
     * @return Response
     */
    public function captcha(): Response
    {
        return Captcha::create();
    }
}
