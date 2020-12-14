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
use app\core\util\Arr as ArrUtil;
use app\core\oss\Client as OssClient;
use app\core\service\Chatroom as ChatroomService;
use app\core\service\User as UserService;
use app\core\service\Friend as FriendService;
use app\model\FriendRequest;
use app\core\identicon\generator\ImageMagickGenerator;
use app\core\oss\Client;
use Identicon\Generator\SvgGenerator;
use think\facade\Cache;
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
        $connections = config('database.connections');
        $conifg = $connections[config('database.default')];
        $dsn = "{$conifg['type']}:host={$conifg['hostname']};dbname={$conifg['database']};charset={$conifg['charset']}";
        $db = new \PDO($dsn, $conifg['username'], $conifg['password'], $conifg['params']);

        $db->exec('DROP FUNCTION IF EXISTS sortByChineseAndEnglish;');
        $count = $db->exec("CREATE FUNCTION `sortByChineseAndEnglish`(P_NAME VARCHAR(255)) RETURNS VARCHAR(255) CHARSET utf8mb4 DETERMINISTIC BEGIN
DECLARE
V_RETURN VARCHAR(255); DECLARE V_BOOL INT DEFAULT 0; DECLARE FIRST_VARCHAR VARCHAR(1);
SET
FIRST_VARCHAR = LEFT(CONVERT(P_NAME USING gbk),
1);
SELECT
FIRST_VARCHAR REGEXP '[a-zA-Z]'
INTO V_BOOL; IF V_BOOL = 1 THEN
SET
V_RETURN = FIRST_VARCHAR; ELSE
SET
V_RETURN = ELT(
INTERVAL(
CONV(
    HEX(
        LEFT(CONVERT(P_NAME USING gbk),
        1)
    ),
    16,
    10
),
0xb0a1,
0xb0c5,
0xb2c1,
0xb4ee,
0xb6ea,
0xb7a2,
0xb8c1,
0xb9fe,
0xbbf7,
0xbfa6,
0xc0ac,
0xc2e8,
0xc4c3,
0xc5b6,
0xc5be,
0xc6da,
0xc8bb,
0xc8f6,
0xcbfa,
0xcdda,
0xcef4,
0xd1b9,
0xd4d1
),
'A',
'B',
'C',
'D',
'E',
'F',
'G',
'H',
'J',
'K',
'L',
'M',
'N',
'O',
'P',
'Q',
'R',
'S',
'T',
'W',
'X',
'Y',
'Z'
);
END IF; RETURN V_RETURN;
END");

        dump($count);

        // dump(Chatroom::join('chat_member', 'chatroom.id = chat_member.chatroom_id')
        //     ->where('chatroom.type', '=', 1)
        //     ->column('chatroom.id'));

        // dump(Chatroom::getLastSql());

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
