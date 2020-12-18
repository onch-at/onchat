<?php

declare(strict_types=1);

namespace app\controller;

use HTMLPurifier;
use app\model\User;
use think\Response;
use think\facade\Db;
use app\BaseController;
use app\model\Chatroom;
use app\model\UserInfo;
use think\facade\Cache;
use app\core\oss\Client;
use HTMLPurifier_Config;
use app\model\ChatMember;
use app\model\ChatRecord;
use OSS\Core\OssException;
use app\model\FriendRequest;
use app\core\util\Arr as ArrUtil;
use app\core\util\Sql as SqlUtil;
use think\captcha\facade\Captcha;
use app\core\util\Date as DateUtil;
use app\core\oss\Client as OssClient;
use Identicon\Generator\SvgGenerator;
use app\core\service\User as UserService;
use app\core\service\Friend as FriendService;
use app\core\service\Chatroom as ChatroomService;
use app\core\identicon\generator\ImageMagickGenerator;

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
        dump(
            ChatMember::where([
                'user_id' => 1,
                'role' => ChatMember::ROLE_HOST
            ])->count()
        );

        // dump(Db::execute("SHOW TABLES LIKE 'chat_record_1_0'"));
        // ChatroomService::addChatRecordTable((string)2000);
        // return response($content, 200, ['Content-Length' => strlen($content)])->contentType('image/png');



        // $identicon = new \Identicon\Identicon(new ImageMagickGenerator());

        // // 存储空间名称
        // $bucket = "onchat";
        // $ossClient = Client::getInstance();
        // echo $ossClient->signUrl($bucket, 'dev/avatar/user/1/9275585a6dd2afdb2034fff04a6d2d7d.webp', 3600, 'GET', [
        //     'x-oss-process' => 'style/original'
        // ]);
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
