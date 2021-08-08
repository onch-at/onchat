<?php

declare(strict_types=1);

namespace app\service;

use app\core\Result;
use app\core\storage\Storage;
use app\facade\UserService;
use app\model\ChatMember as ChatMemberModel;
use app\model\ChatRecord as ChatRecordModel;
use app\model\ChatSession as ChatSessionModel;
use app\model\Chatroom as ChatroomModel;

class ChatSession
{

    /**
     * 获取该用户的聊天会话
     *
     * @return Result
     */
    public function getChatSessions(): Result
    {
        $userId = UserService::getId();

        $storage = Storage::create();

        // 存放私聊聊天室ID列表
        $privateChatroomIdList = [];

        $data = ChatSessionModel::leftJoin('chatroom', 'chat_session.data->chatroomId = chatroom.id')
            ->where([
                'chat_session.user_id' => $userId,
                'chat_session.visible' => true
            ])
            ->field([
                'chat_session.id',
                'chat_session.user_id',
                'chat_session.type',
                'chat_session.data',
                'chat_session.unread',
                'chat_session.sticky',
                'chat_session.create_time',
                'chat_session.update_time',
                'chatroom.name AS title',
                'chatroom.avatar AS avatarThumbnail',
                'chatroom.type AS chatroomType',
            ])
            ->select();

        if ($data->count() === 0) {
            return Result::success([]);
        }

        $query = null;
        $field = [
            'chat_record.*',
            'chat_member.nickname',
        ];
        $max = 'MAX(chat_record.id)';

        foreach ($data as $item) {
            switch ($item->type) {
                    // 聊天室类型会话
                case ChatSessionModel::TYPE_CHATROOM:
                    $chatroomId = $item->data->chatroomId;
                    // 将这些数据丢到data里面
                    $item->data->chatroomType = $item->chatroomType;
                    unset($item->chatroomType);

                    switch ($item->data->chatroomType) {
                        case ChatroomModel::TYPE_GROUP_CHAT:
                            $item->avatarThumbnail = $storage->getThumbnailUrl($item->avatarThumbnail);
                            break;

                        case ChatroomModel::TYPE_PRIVATE_CHAT:
                            $privateChatroomIdList[] = $chatroomId;
                            break;
                    }

                    $table = ChatRecordModel::getTableNameById($chatroomId);
                    $on = 'chat_member.user_id = chat_record.user_id AND chat_member.chatroom_id = ' . $chatroomId;

                    if (!$query) {
                        $query = ChatRecordModel::opt($chatroomId)
                            ->alias('chat_record')
                            ->leftJoin('chat_member', $on)
                            ->where('chat_record.id', '=', function ($query) use ($chatroomId, $table, $max) {
                                $query->table($table)
                                    ->alias('chat_record')
                                    ->where('chat_record.chatroom_id', '=', $chatroomId)
                                    ->fieldRaw($max);
                            })
                            // ->limit(1)
                            ->field($field);
                    } else {
                        $query->unionAll(function ($query) use ($chatroomId, $table, $field, $on, $max) {
                            $query->table($table)
                                ->alias('chat_record')
                                ->leftJoin('chat_member', $on)
                                ->where('chat_record.id', '=', function ($query) use ($chatroomId, $table, $max) {
                                    $query->table($table)
                                        ->alias('chat_record')
                                        ->where('chat_record.chatroom_id', '=', $chatroomId)
                                        ->fieldRaw($max);
                                })
                                ->limit(1)
                                ->field($field);
                        });
                    }
                    break;
            }
        }

        // 最新消息的数据集
        $latestMsgList = $query->select();
        // 好友信息
        $friendInfo = null;

        // 如果有私聊聊天室，就去找到好友的昵称和头像
        if (!empty($privateChatroomIdList)) {
            $friendInfo = ChatMemberModel::join('user_info', 'chat_member.user_id = user_info.user_id')
                ->where([
                    ['chat_member.chatroom_id', 'IN', $privateChatroomIdList],
                    ['chat_member.user_id', '<>', $userId]
                ])
                ->field([
                    'chat_member.chatroom_id',
                    'chat_member.nickname',
                    'user_info.user_id',
                    'user_info.avatar',
                ])
                ->select();
        }

        foreach ($data as $item) {
            switch ($item->type) {
                case ChatSessionModel::TYPE_CHATROOM:
                    // 将最新消息填入
                    $item->content = $latestMsgList->where('chatroom_id', '=', $item->data->chatroomId)->shift();

                    // 如果在聊天室成员表找不到这名用户了（退群了）但是她的消息还在，直接去用户表找
                    if ($item->content && !$item->content->nickname) {
                        $item->content->nickname = UserService::getUsernameById($item->content->user_id);
                    }

                    // 将私聊聊天室的头像，好友昵称填入
                    if ($item->data->chatroomType === ChatroomModel::TYPE_PRIVATE_CHAT && $friendInfo) {
                        $info = $friendInfo->where('chatroom_id', '=', $item->data->chatroomId)->shift();
                        if ($info) {
                            $item->data->userId = $info->user_id;
                            $item->title = $info->nickname;
                            $item->avatarThumbnail = $storage->getThumbnailUrl($info->avatar);
                        }
                    }
                    break;
            }
        }

        return Result::success($data);
    }

    /**
     * 置顶聊天会话
     *
     * @param integer $id 会话ID
     * @param boolean $sticky
     * @return Result
     */
    public function sticky(int $id, $sticky = true): Result
    {
        $userId = UserService::getId();

        ChatSessionModel::update(['sticky' => $sticky], [
            'id'      => $id,
            'user_id' => $userId
        ]);

        return Result::success();
    }

    /**
     * 取消置顶聊天会话
     *
     * @param integer $id
     * @return Result
     */
    public function unsticky(int $id): Result
    {
        return $this->sticky($id, false);
    }

    /**
     * 将聊天会话设置为已读
     *
     * @param integer $id
     * @param integer $unread
     * @return Result
     */
    public function readed(int $id, int $unread = 0): Result
    {
        $userId = UserService::getId();

        ChatSessionModel::update(['unread' => $unread], [
            'id'      => $id,
            'user_id' => $userId
        ]);

        return Result::success();
    }

    /**
     * 将聊天会话设置为未读
     *
     * @param integer $id
     * @return Result
     */
    public function unread(int $id): Result
    {
        return $this->readed($id, 1);
    }

    /**
     * 隐藏会话
     *
     * @param integer $id
     * @return Result
     */
    public function hide(int $id): Result
    {
        $userId = UserService::getId();

        ChatSessionModel::update([
            'visible' => false,
            'sticky' => false,
            'unread' => 0,
        ], [
            'id'      => $id,
            'user_id' => $userId
        ]);

        return Result::success();
    }

    /**
     * 显示群主/管理员/某用户的聊天室通知会话
     *
     * @param integer $chatroomId 聊天室ID
     * @param integer $userId 用户ID
     * @return void
     */
    public function showChatroomNotice(int $chatroomId, int $userId = null)
    {
        $userIdList = ChatMemberModel::where('chatroom_id', '=', $chatroomId)
            ->where(function ($query) {
                $query->whereOr([
                    ['role', '=', ChatMemberModel::ROLE_HOST],
                    ['role', '=', ChatMemberModel::ROLE_MANAGE],
                ]);
            })
            ->column('user_id');

        if ($userId) {
            $userIdList[] = $userId;
        }

        ChatSessionModel::where('type', '=', ChatSessionModel::TYPE_CHATROOM_NOTICE)
            ->where('user_id', 'IN', $userIdList)
            ->update([
                'chat_session.update_time' => time() * 1000,
                'chat_session.visible' => true
            ]);
    }
}
