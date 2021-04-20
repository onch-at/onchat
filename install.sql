-- 为了便于管理，create_time，update_time 手动维护

-- 创建数据库
CREATE DATABASE IF NOT EXISTS onchat DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

-- 进入数据库
USE onchat;

-- 用户表
CREATE TABLE IF NOT EXISTS user (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(30) NOT NULL UNIQUE KEY COMMENT '用户名',
    password    VARCHAR(256) NOT NULL           COMMENT '密码',
    email       VARCHAR(50) NULL UNIQUE KEY     COMMENT '电子邮箱',
    telephone   CHAR(11) NULL UNIQUE KEY        COMMENT '电话号码',
    create_time BIGINT UNSIGNED NOT NULL,
    update_time BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户信息表
CREATE TABLE IF NOT EXISTS user_info (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL    COMMENT '用户ID',
    nickname         VARCHAR(30) NOT NULL     COMMENT '昵称',
    signature        VARCHAR(100) NULL        COMMENT '个性签名',
    mood             TINYINT(1) UNSIGNED NULL COMMENT '心情',
    login_time       BIGINT UNSIGNED NOT NULL COMMENT '登录时间',
    birthday         BIGINT UNSIGNED NULL     COMMENT '生日',
    gender           TINYINT(1) UNSIGNED NULL COMMENT '性别',
    age              TINYINT(1) UNSIGNED NULL COMMENT '年龄',
    constellation    TINYINT(1) UNSIGNED NULL COMMENT '星座',
    avatar           VARCHAR(256) NOT NULL    COMMENT '头像',
    background_image VARCHAR(256) NOT NULL    COMMENT '背景图',
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 聊天室表
CREATE TABLE IF NOT EXISTS chatroom (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(30) NULL             COMMENT '聊天室名字',
    description      VARCHAR(100) NULL            COMMENT '聊天室描述',
    avatar           VARCHAR(256) NULL            COMMENT '聊天室头像URL',
    type             TINYINT(1) UNSIGNED NOT NULL COMMENT '聊天室的类型',
    create_time      BIGINT UNSIGNED NOT NULL,
    update_time      BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 聊天室成员表
CREATE TABLE IF NOT EXISTS chat_member (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    chatroom_id INT UNSIGNED NOT NULL                  COMMENT '聊天室ID',
    user_id     INT UNSIGNED NOT NULL                  COMMENT '用户ID',
    nickname    VARCHAR(30) NOT NULL                   COMMENT '室友昵称',
    role        TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '室友角色',
    unread      TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '未读消息数',
    is_show     BOOLEAN NOT NULL DEFAULT TRUE          COMMENT '是否显示在首页',
    sticky      BOOLEAN NOT NULL DEFAULT FALSE         COMMENT '是否置顶',
    create_time BIGINT UNSIGNED NOT NULL,
    update_time BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (chatroom_id) REFERENCES chatroom(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES user(id)     ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 聊天记录表
-- type：文字，图片，视频，语音，文件，撤回消息
CREATE TABLE IF NOT EXISTS chat_record (
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

-- 好友申请表
-- requester_status:   0->等待验证，1->同意，2->拒绝，3->删除
-- target_status: 0->等待验证，1->同意，2->拒绝，3->删除，4->忽略
CREATE TABLE IF NOT EXISTS friend_request (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    requester_id        INT UNSIGNED NOT NULL                  COMMENT '申请人ID',
    target_id      INT UNSIGNED NOT NULL                  COMMENT '被申请人ID',
    request_reason VARCHAR(50) NULL                       COMMENT '申请原因',
    reject_reason  VARCHAR(50) NULL                       COMMENT '拒绝理由',
    requester_status    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请人状态',
    target_status  TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '被申请人状态',
    requester_alias     VARCHAR(30) NULL                       COMMENT '申请人的别名',
    target_alias   VARCHAR(30) NULL                       COMMENT '被申请人的别名',
    create_time    BIGINT UNSIGNED NOT NULL,
    update_time    BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (requester_id)   REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (target_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE IF NOT EXISTS chat_record (
--     id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
--     username    VARCHAR(30) NOT NULL COMMENT '',
--     password    VARCHAR(60) NOT NULL COMMENT '',
--     email       VARCHAR(50) NULL COMMENT '',
--     telephone   CHAR(11) NULL COMMENT '',
--     create_time BIGINT UNSIGNED NOT NULL,
--     update_time BIGINT UNSIGNED NOT NULL,
--     FOREIGN KEY (id) REFERENCES account(uid) ON DELETE CASCADE ON UPDATE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 创建一个默认聊天室