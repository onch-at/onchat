-- 修改ThinkPHP6的Session中间件源码：在handle()里面
-- $this->app->cookie->set($cookieName, $this->session->getId(), $this->session->getConfig('expire'));

-- 创建数据库
CREATE DATABASE IF NOT EXISTS onchat DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

-- 进入数据库
USE onchat;

-- 用户表
CREATE TABLE IF NOT EXISTS user (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(30) NOT NULL UNIQUE KEY COMMENT '用户名',
    password    VARCHAR(255) NOT NULL           COMMENT '密码',
    email       VARCHAR(50) NULL UNIQUE KEY     COMMENT '电子邮箱',
    telephone   CHAR(11) NULL UNIQUE KEY        COMMENT '电话号码',
    create_time BIGINT UNSIGNED NOT NULL,
    update_time BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 聊天室表
CREATE TABLE IF NOT EXISTS chatroom (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(30) NOT NULL         COMMENT '聊天室名字',
    description      VARCHAR(500) NULL            COMMENT '聊天室描述',
    avatar           VARCHAR(255) NULL            COMMENT '聊天室头像URL',
    avatar_thumbnail VARCHAR(255) NULL            COMMENT '聊天室头像缩略图URL',
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

-- CREATE TABLE IF NOT EXISTS chat_record (
--     id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
--     username    VARCHAR(30) NOT NULL COMMENT '',
--     password    VARCHAR(60) NOT NULL COMMENT '',
--     email       VARCHAR(50) NULL COMMENT '',
--     telephone   CHAR(11) NULL COMMENT '',
--     create_time INT NOT NULL,
--     update_time INT NOT NULL,
--     FOREIGN KEY (id) REFERENCES account(uid) ON DELETE CASCADE ON UPDATE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 创建一个默认聊天室