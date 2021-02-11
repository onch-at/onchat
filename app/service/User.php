<?php

declare(strict_types=1);

namespace app\service;

use app\core\Result;

use think\facade\Db;
use Identicon\Identicon;
use app\facade\ChatroomService;
use app\model\User as UserModel;
use app\util\Str as StrUtil;
use app\util\Date as DateUtil;
use app\core\oss\Client as OssClient;
use app\model\Chatroom as ChatroomModel;
use app\model\UserInfo as UserInfoModel;
use app\model\ChatMember as ChatMemberModel;
use app\model\ChatRecord as ChatRecordModel;
use app\model\ChatSession as ChatSessionModel;
use app\core\identicon\ImageMagickGenerator;

class User
{
    /** 用户名最小长度 */
    const USERNAME_MIN_LENGTH = 5;
    /** 用户名最大长度 */
    const USERNAME_MAX_LENGTH = 15;
    /** 用户密码最小长度 */
    const PASSWORD_MIN_LENGTH = 8;
    /** 用户密码最大长度 */
    const PASSWORD_MAX_LENGTH = 50;
    /** 个性签名 */
    const SIGNATURE_MAX_LENGTH = 100;

    /** 用户已存在 */
    const CODE_USER_EXIST = 1;
    /** 用户不存在 */
    const CODE_USER_NOT_EXIST = 2;
    /** 用户密码错误 */
    const CODE_PASSWORD_ERROR = 3;
    /** 用户密码过短/过长 */
    const CODE_PASSWORD_IRREGULAR  = 4;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_USER_EXIST         => '用户已存在',
        self::CODE_USER_NOT_EXIST     => '用户不存在',
        self::CODE_PASSWORD_ERROR     => '密码错误',
        self::CODE_PASSWORD_IRREGULAR => '密码长度必须在' . self::PASSWORD_MIN_LENGTH . '~' . self::PASSWORD_MAX_LENGTH . '位字符之间',
    ];

    /** 用户登录SESSION名 */
    const SESSION_USER_LOGIN = 'user_login';

    /** 是否开放注册 */
    const CAN_REGISTER = true;

    /** User 字段 */
    const USER_FIELDS = [
        'user.id',
        'user.username',
        'user.email',
        'user.telephone',
        'user.create_time',
        'user.update_time',
        'user_info.nickname',
        'user_info.signature',
        'user_info.mood',
        'user_info.login_time',
        'user_info.birthday',
        'user_info.gender',
        'user_info.age',
        'user_info.constellation',
        'user_info.avatar',
        'user_info.background_image',
    ];

    /**
     * 用户名的正则表达式
     * 匹配字母/汉字/数字/下划线/横杠，5-15位字符
     */
    const USERNAME_PATTERN = "/^([a-z]|[A-Z]|[0-9]|_|-|[\x{4e00}-\x{9fa5}]){" . self::USERNAME_MIN_LENGTH . "," . self::USERNAME_MAX_LENGTH . "}$/u";

    /**
     * 获取储存在SESSION中的用户ID
     *
     * @return integer|null
     */
    public function getId(): ?int
    {
        return session(self::SESSION_USER_LOGIN . '.id');
    }

    /**
     * 获取储存在SESSION中的用户名
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return session(self::SESSION_USER_LOGIN . '.username');
    }

    /**
     * 注册账户
     *
     * @return Result
     */
    public function register(): Result
    {
        $username = input('post.username/s');
        $password = input('post.password/s');

        if (!$username || !$password || !input('post.captcha')) { // 如果参数缺失
            return new Result(Result::CODE_ERROR_PARAM);
        }

        if (!captcha_check(input('post.captcha'))) {
            return new Result(Result::CODE_ERROR_PARAM, '验证码错误！');
        }

        if (!self::CAN_REGISTER) {
            return new Result(Result::CODE_ERROR_UNKNOWN, '暂不开放注册！');
        }

        $username = StrUtil::trimAll($username);
        $password = StrUtil::trimAll($password);

        if (!preg_match(self::USERNAME_PATTERN, $username)) {
            return new Result(Result::CODE_ERROR_PARAM, '用户名格式不规范！');
        }

        $result = $this->checkPassword($password);
        if ($result !== Result::CODE_SUCCESS) { // 如果用户密码不符合规范
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[$result]);
        }

        if (!empty($this->getIdByUsername($username))) { // 如果已经有这个用户了
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::CODE_USER_EXIST]);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!$hash) { // 如果密码散列创建失败
            return new Result(Result::CODE_ERROR_UNKNOWN, '密码散列创建失败');
        }

        $timestamp = time() * 1000;
        $identicon = new Identicon(new ImageMagickGenerator());
        $bucket = OssClient::getBucket();

        // 启动事务
        Db::startTrans();
        try {
            $user = UserModel::create([
                'username'    => $username,
                'password'    => $hash,
                'create_time' => $timestamp,
                'update_time' => $timestamp,
            ]);

            $ossClient = OssClient::getInstance();
            // 如果为调试模式，则将数据存放到dev/目录下
            $object = OssClient::getRootPath() . 'avatar/user/' . $user->id . '/' . md5((string) DateUtil::now()) . '.png';
            // 根据用户ID创建哈希头像
            $content = $identicon->getImageData($user->id, 256, null, '#f5f5f5');
            // 上传到OSS
            $ossClient->putObject($bucket, $object, $content, OssClient::$imageHeadersOptions);

            // 暂存一下用户信息，便于最后直接返回给前端
            $userInfo = [
                'user_id'          => $user->id,
                'nickname'         => $user->username,
                'login_time'       => $timestamp,
                'avatar'           => $object,
                'background_image' => 'http://static.hypergo.net/img/rkph.jpg', // TODO
            ];

            UserInfoModel::create($userInfo);

            $this->saveLoginStatus($user->id, $username, $hash); // 保存登录状态

            ChatroomService::addMember(1, $user->id); // 添加新用户到默认聊天室

            unset($user->password); // 删掉密码

            $userInfo['avatar'] = $ossClient->signImageUrl($object, OssClient::getOriginalImgStylename());
            $userInfo['avatarThumbnail'] = $ossClient->signImageUrl($object);

            // 创建一个聊天室通知会话
            ChatSessionModel::create([
                'user_id' => $user->id,
                'type'    => ChatSessionModel::TYPE_CHATROOM_NOTICE,
                'data'    => [],
                'visible' => false,
                'create_time' => $timestamp,
                'update_time' => $timestamp
            ]);

            // 提交事务
            Db::commit();

            return Result::success($user->toArray() + $userInfo, '注册成功！即将跳转…');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * 用户登录
     *
     * @return Result
     */
    public function login(): Result
    {
        $username = input('post.username/s');
        $password = input('post.password/s');

        if (!$username || !$password) { // 如果参数缺失
            return new Result(Result::CODE_ERROR_PARAM);
        }

        if (!preg_match(self::USERNAME_PATTERN, $username)) {
            return new Result(Result::CODE_ERROR_PARAM, '用户名格式不规范！');
        }

        $result = $this->checkPassword($password);
        if ($result !== Result::CODE_SUCCESS) { // 如果用户密码不符合规范
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[$result]);
        }

        $fields = self::USER_FIELDS;
        $fields[] = 'user.password';

        $user = $this->getInfoByKey('username', $username, $fields);

        if (empty($user)) { // 如果用户不存在
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::CODE_USER_NOT_EXIST]);
        }

        if (!password_verify($password, $user['password'])) { // 如果密码错误
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::CODE_PASSWORD_ERROR]);
        }

        $this->saveLoginStatus($user['id'], $user['username'], $user['password']); // 保存登录状态

        unset($user['password']);

        $ossClient = OssClient::getInstance();
        $object = $user['avatar'];

        $user['avatar'] = $ossClient->signImageUrl($object, OssClient::getOriginalImgStylename());
        $user['avatarThumbnail'] = $ossClient->signImageUrl($object);

        return Result::success($user, '登录成功！即将跳转…');
    }

    /**
     * 清除登录Session，退出登录
     *
     * @return void
     */
    public function logout(): void
    {
        session(self::SESSION_USER_LOGIN, null);
    }

    /**
     * 设置用户登录Session，用于保存登录状态
     *
     * @param integer $id 用户ID
     * @param string $username 用户名
     * @param string $hashPassword 密码密文
     * @return void
     */
    public function saveLoginStatus(int $id, string $username, string $hashPassword): void
    {
        session(self::SESSION_USER_LOGIN, [
            'id'       => $id,
            'username' => $username,
            'password' => $hashPassword,
        ]);
    }

    /**
     * 通过用户标识获取用户信息
     *
     * @param string $key 用户标识名
     * @param mixed $value 用户标识值
     * @param string|array $field 需要获取的字段名
     * @return array
     */
    public function getInfoByKey(string $key, $value, $field): array
    {

        return UserModel::where($key == 'id' ? 'user.id' : $key, '=', $value)
            ->join('user_info', 'user_info.user_id = user.id')
            ->field($field)
            ->findOrEmpty()
            ->toArray();
    }

    /**
     * 通过用户ID获取User
     *
     * @param integer $id
     * @return Result
     */
    public function getUserById(int $id): Result
    {
        $user = UserModel::join('user_info', 'user_info.user_id = user.id')
            ->where('user.id', '=', $id)
            ->field(self::USER_FIELDS)
            ->find();

        if (!$user) {
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::CODE_USER_NOT_EXIST]);
        }

        $ossClient = OssClient::getInstance();
        $object = $user->avatar;

        $user->avatar = $ossClient->signImageUrl($object, OssClient::getOriginalImgStylename());
        $user->avatarThumbnail = $ossClient->signImageUrl($object);

        return Result::success($user->toArray());
    }

    /**
     * 通过用户名获取User
     *
     * @param string $username
     * @return Result
     */
    public function getUserByUsername(string $username): Result
    {
        $user = UserModel::where('user.username', '=', $username)->join('user_info', 'user_info.user_id = user.id')
            ->field(self::USER_FIELDS)->find();

        if (!$user) {
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::CODE_USER_NOT_EXIST]);
        }

        $ossClient = OssClient::getInstance();
        $object = $user->avatar;

        $user->avatar = $ossClient->signImageUrl($object, OssClient::getOriginalImgStylename());
        $user->avatarThumbnail = $ossClient->signImageUrl($object);

        return Result::success($user->toArray());
    }

    /**
     * 获得用户在聊天室中的昵称
     *
     * @param integer $id 用户ID
     * @param integer $chatroomId
     * @return string|null
     */
    public function getNicknameInChatroom(int $id, int $chatroomId): ?string
    {
        return ChatMemberModel::where([
            'user_id'     => $id,
            'chatroom_id' => $chatroomId
        ])->value('nickname');
    }

    /**
     * 通过用户ID获取用户名
     *
     * @param integer $id 用户ID
     * @return string|null
     */
    public function getUsernameById(int $id): ?string
    {
        return UserModel::where('id', '=', $id)->value('username');
    }

    /**
     * 通过用户名获取用户ID
     *
     * @param string $username 用户名
     * @return integer
     */
    public function getIdByUsername(string $username): ?int
    {
        return UserModel::where('username', '=', $username)->value('id');
    }

    /**
     * 检查用户是否已经登录/处于登录状态
     * 如果已登录，则返回User, 否则返回false
     *
     * @return Result
     */
    public function checkLogin(): Result
    {
        $session = session(self::SESSION_USER_LOGIN);
        if (empty($session)) { // 如果没有登录的Session
            return Result::success(false);
        }

        $fields = self::USER_FIELDS;
        $fields[] = 'user.password';
        $user = $this->getInfoByKey('id', $session['id'], $fields);

        if ($session['password'] !== $user['password']) { // 如果密码错误
            return Result::success(false);
        }

        $ossClient = OssClient::getInstance();
        $object = $user['avatar'];

        $user['avatar'] = $ossClient->signImageUrl($object, OssClient::getOriginalImgStylename());
        $user['avatarThumbnail'] = $ossClient->signImageUrl($object);

        unset($user['password']);

        return Result::success($user);
    }

    /**
     * 检查用户密码是否符合规范
     *
     * @param string $password
     * @return integer
     */
    public function checkPassword(string $password): int
    {
        $length = mb_strlen($password, 'utf-8');

        if ($length < self::PASSWORD_MIN_LENGTH || $length > self::PASSWORD_MAX_LENGTH) {
            return self::CODE_PASSWORD_IRREGULAR;
        }

        return Result::CODE_SUCCESS;
    }

    /**
     * 检测邮箱是否可用
     *
     * @param string $email
     * @return Result
     */
    public function checkEmail(?string $email = null): Result
    {
        if (!$email) {
            $email = input('get.email/s');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Result::success(false);
        }

        return Result::success(UserModel::where('email', '=', $email)->field('id')->find() === null);
    }

    /**
     * 上传头像
     *
     * @return Result
     */
    public function avatar(): Result
    {
        $userId = $this->getId();

        $bucket = OssClient::getBucket();

        // 如果为调试模式，则将数据存放到dev/目录下
        $root = OssClient::getRootPath();

        // 由于搜索用户所有历史头像
        $options = [
            // 文件路径前缀
            'prefix' => $root . 'avatar/user/' . $userId . '/',
            // 最大数量
            'max-keys' => 20,
        ];

        // 用户头像数量最大值
        $maxCount = 10;

        try {
            $image = request()->file('image');

            $mine = $image->getMime();

            if (!in_array($mine, ['image/webp', 'image/jpeg', 'image/png'])) {
                return new Result(Result::CODE_ERROR_PARAM, '文件格式错误，仅接受格式为webp/jpeg/png的图片文件');
            }

            if ($image->getSize() > 1048576) { // 1MB
                return new Result(Result::CODE_ERROR_PARAM, '文件体积过大，仅接受体积为1MB以内的文件');
            }

            $ossClient = OssClient::getInstance();

            $object = $root . 'avatar/user/' . $userId . '/' . md5((string) DateUtil::now()) . '.' . substr($mine, 6);
            // 上传到OSS
            $ossClient->uploadFile($bucket, $object, $image->getRealPath(), OssClient::$imageHeadersOptions);

            // 列举用户所有头像
            $objectList = $ossClient->listObjects($bucket, $options)->getObjectList();

            $count = count($objectList);

            // 如果用户的头像大于10张
            if ($count > $maxCount) {
                // 按照时间进行升序
                usort($objectList, function ($a, $b) {
                    return strtotime($a->getLastModified()) - strtotime($b->getLastModified());
                });

                // 需要删除的OBJ
                $objects = [];

                $num = $count - $maxCount;
                for ($i = 0; $i < $num; $i++) {
                    $objects[] = $objectList[$i]->getKey();
                }

                // 把超过的删除
                $ossClient->deleteObjects($bucket, $objects);
            }

            // 更新新头像
            UserInfoModel::update(['avatar' => $object], [
                'user_id' => $userId
            ]);

            return Result::success([
                'avatar'          => $ossClient->signImageUrl($object, OssClient::getOriginalImgStylename()),
                'avatarThumbnail' => $ossClient->signImageUrl($object)
            ]);
        } catch (\Exception $e) {
            return new Result(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * 查询该用户下所有的聊天室
     *
     * @return array
     */
    public function getChatrooms($userId = null): array
    {
        return ChatMemberModel::join('chatroom', 'chat_member.chatroom_id = chatroom.id')
            ->where('chat_member.user_id', '=', $userId ?: $this->getId())
            ->field('chatroom.*')
            ->select()
            ->toArray();
    }

    /**
     * 获取该用户的聊天会话
     *
     * @return Result
     */
    public function getChatSessions(): Result
    {
        $userId = $this->getId();

        $ossClient = OssClient::getInstance();

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
            ->select()
            ->toArray();

        $query = null;
        $field = [
            'chat_record.*',
            'chat_member.nickname',
        ];
        $max = 'MAX(chat_record.id)';

        foreach ($data as $key => $value) {
            switch ($value['type']) {
                    // 聊天室类型会话
                case ChatSessionModel::TYPE_CHATROOM:
                    $chatroomId = $value['data']->chatroomId;
                    // 将这些数据丢到data里面
                    $value['data']->chatroomType = $value['chatroomType'];
                    unset($data[$key]['chatroomType']);

                    switch ($value['data']->chatroomType) {
                        case ChatroomModel::TYPE_GROUP_CHAT:
                            $data[$key]['avatarThumbnail'] = $ossClient->signImageUrl($value['avatarThumbnail']);
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
        // 聊天室最新消息
        $latestMsg = null;
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

        foreach ($data as $key => $value) {
            switch ($value['type']) {
                case ChatSessionModel::TYPE_CHATROOM:
                    $latestMsg = $latestMsgList->where('chatroom_id', '=', $value['data']->chatroomId)->shift();
                    // 将最新消息填入
                    if ($latestMsg) {
                        $data[$key]['content'] = $latestMsg->toArray();
                    }

                    // 将私聊聊天室的头像，好友昵称填入
                    if ($value['data']->chatroomType == ChatroomModel::TYPE_PRIVATE_CHAT && $friendInfo) {
                        $info = $friendInfo->where('chatroom_id', '=', $value['data']->chatroomId)->shift();
                        if ($info) {
                            $data[$key]['data']->userId = $info->user_id;
                            $data[$key]['title'] = $info->nickname;
                            $data[$key]['avatarThumbnail'] = $ossClient->signImageUrl($info->avatar);
                        }
                    }
                    break;
            }
        }

        return Result::success($data);
    }

    /**
     * 获取私聊聊天室列表
     *
     * @return Result
     */
    public function getPrivateChatrooms(): Result
    {
        $userId = $this->getId();
        $ossClient = OssClient::getInstance();

        $data = ChatMemberModel::join('user_info', 'user_info.user_id = chat_member.user_id')
            ->where('chat_member.chatroom_id', 'IN', function ($query)  use ($userId) {
                // 私聊聊天室ID列表
                $query->table('chatroom')->join('chat_member', 'chatroom.id = chat_member.chatroom_id')->where([
                    'chatroom.type' =>  ChatroomModel::TYPE_PRIVATE_CHAT,
                    'chat_member.user_id' => $userId
                ])->field('chatroom.id');
            })
            ->where('chat_member.user_id', '<>', $userId)
            ->field([
                'chat_member.id',
                'chat_member.user_id as friendId',
                'chat_member.chatroom_id',
                'chat_member.nickname AS title',
                'chat_member.create_time',
                'chat_member.update_time',
                'user_info.signature AS content',
                'user_info.avatar AS avatarThumbnail',
            ])
            ->select()
            ->toArray();

        foreach ($data as $key => $value) {
            $data[$key]['userId'] = $userId;
            $data[$key]['type'] = ChatSessionModel::TYPE_CHATROOM;
            $data[$key]['data'] = [
                'chatroomId' => $value['chatroom_id'],
                'chatroomType' => ChatroomModel::TYPE_PRIVATE_CHAT,
                'userId' => $value['friendId']
            ];
            $data[$key]['avatarThumbnail'] = $ossClient->signImageUrl($value['avatarThumbnail']);

            unset($data[$key]['friendId'], $data[$key]['chatroom_id']);
        }

        return Result::success($data);
    }

    /**
     * 获取群聊聊天室列表
     *
     * @return Result
     */
    public function getGroupChatrooms(): Result
    {
        $userId = $this->getId();
        $ossClient = OssClient::getInstance();

        $data = ChatMemberModel::join('chatroom', 'chatroom.id = chat_member.chatroom_id')
            ->where([
                'chat_member.user_id' => $userId,
                'chatroom.type' => ChatroomModel::TYPE_GROUP_CHAT
            ])
            ->field([
                'chat_member.id',
                'chat_member.chatroom_id',
                'chat_member.create_time',
                'chat_member.update_time',
                'chatroom.name AS title',
                'chatroom.description AS content',
                'chatroom.avatar AS avatarThumbnail',
            ])
            ->select()
            ->toArray();

        foreach ($data as $key => $value) {
            $data[$key]['userId'] = $userId;
            $data[$key]['type'] = ChatSessionModel::TYPE_CHATROOM;
            $data[$key]['data'] = [
                'chatroomId' => $value['chatroom_id'],
                'chatroomType' => ChatroomModel::TYPE_GROUP_CHAT
            ];
            $data[$key]['avatarThumbnail'] = $ossClient->signImageUrl($value['avatarThumbnail']);

            unset($data[$key]['chatroom_id']);
        }

        return Result::success($data);
    }

    /**
     * 置顶聊天列表子项
     *
     * @param integer $id 会话ID
     * @param boolean $sticky
     * @return Result
     */
    public function stickyChatSession(int $id, $sticky = true): Result
    {
        $userId = $this->getId();

        $chatSession = ChatSessionModel::where([
            'id'      => $id,
            'user_id' => $userId
        ])->find();

        if (!$chatSession) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        $chatSession->sticky = $sticky;
        $chatSession->save();

        return Result::success();
    }

    /**
     * 取消置顶聊天列表子项
     *
     * @param integer $id 聊天室成员表ID
     * @return Result
     */
    public function unstickyChatSession(int $id): Result
    {
        return $this->stickyChatSession($id, false);
    }

    /**
     * 将聊天列表子项设置为已读
     *
     * @param integer $id
     * @param integer $unread
     * @return Result
     */
    public function readedChatSession(int $id, int $unread = 0): Result
    {
        $userId = $this->getId();

        $chatSession = ChatSessionModel::where([
            'id'      => $id,
            'user_id' => $userId
        ])->find();

        if (!$chatSession) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        $chatSession->unread = $unread;
        $chatSession->save();

        return Result::success();
    }

    /**
     * 将聊天列表子项设置为未读
     *
     * @param integer $id
     * @return Result
     */
    public function unreadChatSession(int $id): Result
    {
        return $this->readedChatSession($id, 1);
    }

    /**
     * 保存用户信息
     *
     * @return Result
     */
    public function saveUserInfo(): Result
    {
        $userId = $this->getId();

        $nickname      = input('put.nickname/s') ?: $this->getUsername();
        $signature     = input('put.signature/s');
        $mood          = input('put.mood/d');
        $birthday      = input('put.birthday/d');
        $gender        = input('put.gender/d');
        $age           = isset($birthday) ? DateUtil::getAge((int) $birthday / 1000) : null;
        $constellation = isset($birthday) ? DateUtil::getConstellation((int) $birthday / 1000) : null;

        if ($signature) {
            if (mb_strlen(StrUtil::trimAll($signature), 'utf-8') == 0) {
                $signature = null;
            } else {
                $signature = trim($signature);

                if (mb_strlen($signature, 'utf-8') > self::SIGNATURE_MAX_LENGTH) {
                    return new Result(Result::CODE_ERROR_PARAM);
                }
            }
        }

        if (!isset($mood)) {
            $mood = 0;
        }

        if (!isset($gender)) {
            $gender = 2;
        }

        $userInfo = UserInfoModel::update([
            'nickname'      => $nickname,
            'signature'     => $signature,
            'mood'          => $mood,
            'birthday'      => $birthday,
            'gender'        => $gender,
            'age'           => $age,
            'constellation' => $constellation,
        ], [
            'user_id' => $userId
        ]);

        return Result::success($userInfo->toArray());
    }
}
