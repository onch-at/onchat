<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\ChatMember;
use app\model\User;
use app\model\ChatRecord;
use app\model\Chatroom;
use think\captcha\facade\Captcha;
use think\facade\Db;
use think\Response;
use app\core\util\Sql as SqlUtil;

use app\core\handler\Chatroom as ChatroomHandler;
use app\core\handler\User as UserHandler;
use app\core\handler\Friend as FriendHandler;
use think\console\Output;
use think\facade\Cache;

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

            Db::execute("
                CREATE TABLE IF NOT EXISTS chat_record_" . $chatroom->id . " (
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    chatroom_id INT UNSIGNED NOT NULL          COMMENT '聊天室ID',
                    user_id     INT UNSIGNED NULL              COMMENT '消息发送者ID',
                    type        TINYINT(1) UNSIGNED NOT NULL   COMMENT '消息类型',
                    data        JSON NOT NULL                  COMMENT '消息数据体',
                    reply_id    INT UNSIGNED NULL              COMMENT '回复消息的消息记录ID',
                    create_time BIGINT UNSIGNED NOT NULL,
                    FOREIGN KEY (chatroom_id) REFERENCES chatroom(id) ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (user_id)     REFERENCES user(id)     ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

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
        // $this->addChatroom('TEST CHATROOM 3');
        // $this->addChatroom('世界都在聊');
        // FriendHandler::request(1, 2, '没有理由', '我的小号');
        dump(FriendHandler::request(1, 3, '没有理由', '我的小号'));
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
