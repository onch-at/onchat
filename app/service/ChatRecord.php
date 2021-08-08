<?php

declare(strict_types=1);

namespace app\service;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use app\constant\MessageType;
use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\core\Result;
use app\core\storage\Storage;
use app\entity\ImageMessage;
use app\entity\Message;
use app\entity\VoiceMessage;
use app\facade\FdTable;
use app\facade\UserService;
use app\model\ChatRecord as ChatRecordModel;
use app\model\ChatSession as ChatSessionModel;
use app\model\Chatroom as ChatroomModel;
use app\model\UserInfo as UserInfoModel;
use app\util\File as FileUtil;
use think\Container;
use think\facade\Db;
use think\facade\Request;
use think\swoole\Websocket;

class ChatRecord
{

    /** 每次查询的消息行数 */
    const MSG_ROWS = 15;

    /**
     * 查询消息记录
     * 按照消息ID查询，若消息ID为0，则为初次查询，否则查询传入的消息ID之前的消息
     *
     * @param integer $id 消息ID
     * @param integer $chatroomId 聊天室ID
     * @return Result
     */
    public function getRecords(int $id, int $chatroomId): Result
    {
        $userId = UserService::getId();

        // 查询的时候，顺带把未读消息数归零
        ChatSessionModel::update([
            'unread' => 0,
            'visible' => true,
        ], [
            'user_id'          => $userId,
            'type'             => ChatSessionModel::TYPE_CHATROOM,
            'data->chatroomId' => $chatroomId
        ]);

        $query = ChatRecordModel::opt($chatroomId)
            ->alias('chat_record')
            ->leftJoin('user_info', 'user_info.user_id = chat_record.user_id')
            ->leftJoin('chat_member', 'chat_member.user_id = chat_record.user_id AND chat_member.chatroom_id =' . $chatroomId)
            ->where('chat_record.chatroom_id', '=', $chatroomId)
            ->field([
                'chat_member.role',
                'chat_member.nickname',
                'user_info.avatar AS avatarThumbnail',
                'chat_record.*',
            ])
            ->order('chat_record.id', 'DESC')
            ->limit(self::MSG_ROWS);

        // 如果msgId为0，则代表初次查询
        $data    = ($id === 0 ? $query : $query->where('chat_record.id', '<', $id))->select();
        $storage = Storage::create();

        foreach ($data as $item) {
            // 如果是用户发的消息
            if ($item->user_id) {
                $item->avatarThumbnail = $storage->getThumbnailUrl($item->avatarThumbnail);

                // 如果在聊天室成员表找不到这名用户了（退群了）但是她的消息还在，直接去用户表找
                if (!isset($item->nickname)) {
                    $item->nickname = UserService::getUsernameById($item->user_id);
                }
            }

            switch ($item->type) {
                case MessageType::CHAT_INVITATION:
                    $chatroom = ChatroomModel::find($item->data->chatroomId);
                    $item->data->name            = $chatroom ? $chatroom->name : '聊天室已解散';
                    $item->data->description     = $chatroom ? $chatroom->description : null;
                    $item->data->avatarThumbnail = $chatroom ? $storage->getThumbnailUrl($chatroom->avatar) : null;
                    break;

                case MessageType::IMAGE:
                    $url = $item->data->filename;
                    $item->data->url          = $storage->getUrl($url);
                    $item->data->thumbnailUrl = $storage->getThumbnailUrl($url);
                    break;

                case MessageType::VOICE:
                    $url = $item->data->filename;
                    $item->data->url = $storage->getUrl($url);
                    break;

                case MessageType::TIPS:
                    break;
            }
        }

        return Result::success($data);
    }

    /**
     * 添加消息
     *
     * @param Message $message 消息体
     * @return Result
     */
    public function addRecord(Message $message): Result
    {
        $userId     = $message->userId;
        $chatroomId = $message->chatroomId;

        $nickname = UserService::getNicknameInChatroom($userId, $chatroomId);
        if (!$nickname) { // 如果拿不到就说明当前用户不在这个聊天室
            return Result::create(Result::CODE_NO_PERMISSION);
        }

        // 启动事务
        Db::startTrans();
        try {
            $timestamp = time() * 1000;

            $id = (int) ChatRecordModel::opt($chatroomId)->insertGetId([
                'chatroom_id' => $message->chatroomId,
                'user_id'     => $message->userId,
                'type'        => $message->type,
                'data'        => $message->data,
                'reply_id'    => $message->replyId,
                'create_time' => $timestamp
            ]);

            ChatSessionModel::update([
                'visible' => true,
                'update_time' => $timestamp,
                // 如果是该用户的，则归零；
                // 如果不是该用户的，且小于100，则递增；否则直接100
                'unread'      => Db::raw('CASE WHEN user_id = ' . $userId . ' THEN 0 ELSE CASE WHEN unread < 100 THEN unread + 1 ELSE 100 END END')
            ], [
                'type'             => ChatSessionModel::TYPE_CHATROOM,
                'data->chatroomId' => $chatroomId
            ]);

            $storage = Storage::create();

            $memberInfo = UserInfoModel::join('chat_member', 'user_info.user_id = chat_member.user_id AND chat_member.chatroom_id = ' . $chatroomId)
                ->where('user_info.user_id', '=', $userId)
                ->field([
                    'user_info.avatar',
                    'chat_member.role'
                ])
                ->find();

            $message->id              = $id;
            $message->nickname        = $nickname;
            $message->avatarThumbnail = $storage->getThumbnailUrl($memberInfo->avatar);
            $message->role            = $memberInfo->role;
            $message->createTime      = $timestamp;

            switch ($message->type) {
                case MessageType::CHAT_INVITATION:
                    $chatroom = ChatroomModel::find($message->data->chatroomId);
                    $message->data->name            = $chatroom ? $chatroom->name : '聊天室已解散';
                    $message->data->description     = $chatroom ? $chatroom->description : null;
                    $message->data->avatarThumbnail = $chatroom ? $storage->getThumbnailUrl($chatroom->avatar) : null;
                    break;

                case MessageType::IMAGE:
                    $url = $message->data->filename;
                    $message->data->url          = $storage->getUrl($url);
                    $message->data->thumbnailUrl = $storage->getThumbnailUrl($url);
                    break;

                case MessageType::VOICE:
                    $url = $message->data->filename;
                    $message->data->url = $storage->getUrl($url);
                    break;
            }

            // 提交事务
            Db::commit();
            return Result::success($message);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return Result::unknown($e->getMessage());
        }
    }

    /**
     * 撤回消息
     *
     * @param integer $id 消息ID
     * @param integer $userId 用户ID
     * @param integer $chatroomId 房间号
     * @return Result
     */
    public function revokeRecord(int $id, int $userId, int $chatroomId): Result
    {
        $query = ChatRecordModel::opt($chatroomId)->where('id', '=', $id);
        $msg = $query->find();
        // 如果没找到这条消息
        if (!$msg) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        // 如果消息不是它本人发的 或者 已经超时了
        if ($msg->user_id !== $userId || time() > $msg->create_time + 120000) {
            return Result::create(Result::CODE_NO_PERMISSION);
        }

        // 启动事务
        Db::startTrans();
        try {
            // 如果消息删除失败
            if ($query->delete() === 0) {
                Db::rollback();
                return Result::unknown();
            }

            // 如果是语音消息，则删除语音文件
            if ($msg->type === MessageType::VOICE) {
                $storage = Storage::create();
                $storage->delete($msg->data->filename);
            }

            ChatSessionModel::update([
                'update_time' => time() * 1000,
                // 如果消息不是该用户的，且未读消息数小于100，则递减（未读消息数最多储存到100，因为客户端会显示99+）
                'unread'      => Db::raw('CASE WHEN user_id != ' . $userId . ' AND unread BETWEEN 1 AND 100 THEN unread-1 ELSE unread END'),
            ], [
                'type' => ChatSessionModel::TYPE_CHATROOM,
                'data->chatroomId' => $chatroomId
            ]);

            // 提交事务
            Db::commit();
            return Result::success(['chatroomId' => $chatroomId, 'msgId' => $id]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return Result::unknown($e->getMessage());
        }
    }

    /**
     * 上传图片
     *
     * @param integer $chatroomId 聊天室ID
     * @return Result
     */
    public function image(int $chatroomId): Result
    {
        $userId    = UserService::getId();
        $websocket = Container::getInstance()->make(Websocket::class);
        $image     = Request::file('image');

        try {
            $storage = Storage::create();
            $path    = $storage->getRootPath() . 'image/';
            $file    = $image->md5() . '.' . FileUtil::getExtension($image);
            $result  = $storage->save($path, $file, $image);

            if ($result->isError()) {
                return $result;
            }

            [$width, $height] = getimagesize($image->getPathname());

            $msg = new Message(MessageType::IMAGE);
            $msg->userId     = $userId;
            $msg->chatroomId = $chatroomId;
            $msg->tempId     = Request::param('tempId');
            $msg->data       = new ImageMessage($path . $file, $width, $height);

            $result = $this->addRecord($msg);

            if ($result->isSuccess()) {
                $websocket->to(SocketRoomPrefix::CHATROOM . $chatroomId)->emit(SocketEvent::MESSAGE, $result);
            } else {
                $websocket->to(FdTable::getFd($userId))->emit(SocketEvent::MESSAGE, $result);
            }

            return $result;
        } catch (\Exception $e) {
            return Result::unknown($e->getMessage());
        }
    }

    /**
     * 上传语音
     *
     * @param integer $chatroomId 聊天室ID
     * @return Result
     */
    public function voice(int $chatroomId): Result
    {
        $userId    = UserService::getId();
        $websocket = Container::getInstance()->make(Websocket::class);
        $voice     = Request::file('voice');
        $file      = $voice->md5() . '.mp3';
        $temp      = sys_get_temp_dir() . '/' . $file; // 存到临时目录中
        // 设置音频通道为1，比特率为64（比特率默认为128，这里将其减半）
        $mp3       = (new Mp3())->setAudioChannels(1)->setAudioKiloBitrate(64);

        try {
            // 转码保存并获得音频时长
            $duration =  +FFMpeg::create()
                ->open($voice)
                ->save($mp3, $temp)
                ->getFFProbe()
                ->format($temp)
                ->get('duration');

            if ($duration > 61) {
                return Result::create(Result::CODE_PARAM_ERROR, '语音消息时长过长');
            }

            $storage = Storage::create();
            $path    = $storage->getRootPath() . "voice/chatroom/{$chatroomId}/";
            $result  = $storage->save($path, $file, $temp);

            if ($result->isError()) {
                return $result;
            }

            $msg = new Message(MessageType::VOICE);
            $msg->userId     = $userId;
            $msg->chatroomId = $chatroomId;
            $msg->tempId     = Request::param('tempId');
            $msg->data       = new VoiceMessage($path . $file, $duration);

            $result = $this->addRecord($msg);

            if ($result->isSuccess()) {
                $websocket->to(SocketRoomPrefix::CHATROOM . $chatroomId)->emit(SocketEvent::MESSAGE, $result);
            } else {
                $websocket->to(FdTable::getFd($userId))->emit(SocketEvent::MESSAGE, $result);
            }

            return $result;
        } catch (\Exception $e) {
            return Result::unknown($e->getMessage());
        }
    }
}
