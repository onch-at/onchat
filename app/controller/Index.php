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

use app\common\handler\Chatroom as ChatroomHandler;

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
        });
    }

    public function searchChatroom() {
        return User::find(2)->chatrooms()->select()->toArray();
    }

    public function index()
    {
        // $userId = 1;
        // Db::transaction(function () use ($userId) {
        //     $userId = 1;
        //     // 创建一个聊天室
        //     $chatroom = Chatroom::create([
        //         'name' => 'Test',
        //         'type' => 0,
        //     ]);

        //     // 添加聊天成员
        //     $chatMember = ChatMember::create([
        //         'chatroom_id' => $chatroom->id,
        //         'user_id'     => $userId,
        //         'nickname'    => TODO  昵称
        //     ]);
        // });
        // dump(Chatroom::where('id', 'IN', User::find(1)->chatMember()->column('chatroom_id'))->select()->toArray());
        // $list = ChatMember::with('user')->where('chatroom_id', '=', 1)->select();

        // $temp = [];
        // foreach ($list as $item) {
        //     $temp[] = $item->user->toArray();
        // }

        // return dump($temp);

    //     dump(User::find(1)->chatrooms()->select()->toArray());
            // $this->addChatroom('OnChat');
            // $this->addChatroom('TEST CHATROOM');

            // dump($this->searchChatroom());

            // for ($i=0; $i < 10; $i++) { 
            //     Chatroom::find(1)->chatRecord()->save([
            //         'user_id' => 1,
            //         'type' => 1,
            //         'content' => $i*1000
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

            // dump(User::where('id', '=', 1)->value('username'));

        // Chatroom::find(1)->chatRecord()->paginateX([
        //     'list_rows'=> 10,
        //     'page' => 2,
        // ])->each(function($item, $key){
        //     dump($item->toArray());
        // });
        // dump(User::find(2)->chatMember()->find(2));
        dump(User::find(2)->chatMember()->where('chatroom_id', '=',1)->value('nickname'));
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
