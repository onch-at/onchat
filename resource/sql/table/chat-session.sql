-- 聊天会话表
CREATE TABLE IF NOT EXISTS chat_session (
    id          INT        UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT        UNSIGNED NOT NULL               COMMENT '用户ID',
    type        TINYINT(1) UNSIGNED NOT NULL               COMMENT '会话类型',
    data        JSON                    NULL               COMMENT '附加数据',
    unread      TINYINT(1) UNSIGNED NOT NULL DEFAULT 0     COMMENT '未读消息数',
    visible     BOOLEAN             NOT NULL DEFAULT TRUE  COMMENT '是否显示',
    sticky      BOOLEAN             NOT NULL DEFAULT FALSE COMMENT '是否置顶',
    create_time BIGINT     UNSIGNED NOT NULL,
    update_time BIGINT     UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;