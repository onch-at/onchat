-- 聊天记录表
-- type：文字，图片，视频，语音，文件，撤回消息
CREATE TABLE IF NOT EXISTS chat_record_{{ index }} (
    id          INT        UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    chatroom_id INT        UNSIGNED NOT NULL COMMENT '聊天室ID',
    user_id     INT        UNSIGNED     NULL COMMENT '消息发送者ID',
    type        TINYINT(1) UNSIGNED NOT NULL COMMENT '消息类型',
    data        JSON                NOT NULL COMMENT '消息数据体',
    reply_id    INT        UNSIGNED     NULL COMMENT '回复消息的消息记录ID',
    create_time BIGINT     UNSIGNED NOT NULL,
    FOREIGN KEY (chatroom_id) REFERENCES chatroom(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES user(id)     ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;