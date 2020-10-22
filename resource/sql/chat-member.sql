-- 聊天室成员表
CREATE TABLE IF NOT EXISTS chat_member (
    id          INT         UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    chatroom_id INT         UNSIGNED NOT NULL               COMMENT '聊天室ID',
    user_id     INT         UNSIGNED NOT NULL               COMMENT '用户ID',
    nickname    VARCHAR(30)          NOT NULL               COMMENT '室友昵称',
    role        TINYINT(1)  UNSIGNED NOT NULL DEFAULT 0     COMMENT '室友角色',
    unread      TINYINT(1)  UNSIGNED NOT NULL DEFAULT 0     COMMENT '未读消息数',
    is_show     BOOLEAN              NOT NULL DEFAULT TRUE  COMMENT '是否显示在首页',
    sticky      BOOLEAN              NOT NULL DEFAULT FALSE COMMENT '是否置顶',
    create_time BIGINT      UNSIGNED NOT NULL,
    update_time BIGINT      UNSIGNED NOT NULL,
    FOREIGN KEY (chatroom_id) REFERENCES chatroom(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES user(id)     ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;