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

use app\core\handler\Chatroom as ChatroomHandler;
use app\core\handler\User as UserHandler;

class Index extends BaseController
{
    public function addChatroom($name)
    {
        Db::transaction(function () use ($name) {
            // 创建一个聊天室
            $chatroom = Chatroom::create([
                'name' => $name,
                'type' => 0,
            ]);

            // 添加聊天成员
            User::find(1)->chatrooms()->attach($chatroom->id, [
                'role' => 0,
                'nickname' => 'HyperLife1119'
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
        // $this->addChatroom('TEST CHATROOM');
        // $this->addChatroom('OnChat');

        // for ($i=0; $i < 10; $i++) { 
        //     Chatroom::find(1)->chatRecord()->save([
        //         'user_id' => 1,
        //         'type' => 1,
        //         'content' => $i*12345
        //     ]);
        // }

        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 1,
        //     'type' => 1,
        //     'content' => '哈喽！！！'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 1,
        //     'type' => 1,
        //     'content' => '有人吗？😅'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 2,
        //     'type' => 1,
        //     'content' => '有的，'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 1,
        //     'type' => 1,
        //     'content' => 'emmm'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 2,
        //     'type' => 1,
        //     'content' => '。。。'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 2,
        //     'type' => 1,
        //     'content' => 'Hello, World!'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 1,
        //     'type' => 1,
        //     'content' => '你好，世界！'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 2,
        //     'type' => 1,
        //     'content' => '没错！'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 1,
        //     'type' => 1,
        //     'content' => '2333'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 1,
        //     'type' => 1,
        //     'content' => '嗯嗯'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 2,
        //     'type' => 1,
        //     'content' => '啊咧啊咧啊咧啊咧'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 2,
        //     'type' => 1,
        //     'content' => '啊哈哈哈哈'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 2,
        //     'type' => 1,
        //     'content' => '😁'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 1,
        //     'type' => 1,
        //     'content' => '。。。'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 2,
        //     'type' => 1,
        //     'content' => '😊'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 1,
        //     'type' => 1,
        //     'content' => '好的'
        // ]);
        // Chatroom::find(1)->chatRecord()->save([
        //     'user_id' => 1,
        //     'type' => 1,
        //     'content' => 'OK'
        // ]);

        dump(UserHandler::getChatList());
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
