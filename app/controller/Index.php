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
                'role' => 0
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
            // $this->addChatroom('世界都在聊');

            // dump($this->searchChatroom());

            dump(
                User::find(1)
                ->chatMember()
                ->field([
                    'chat_member.id',
                    'chat_member.chatroom_id',
                    'chat_member.unread',
                    'chat_member.sticky',
                    'chat_member.create_time',
                    'chat_member.update_time',
                    'chatroom.name',
                    'chatroom.avatar',
                    'chatroom.type'
                ])
                ->where('chat_member.is_show', '=', true)
                ->join('chatroom', 'chat_member.chatroom_id = chatroom.id')
                ->select()
                ->toArray()
            );
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
