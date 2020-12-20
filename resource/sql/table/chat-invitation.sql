-- 聊天邀请表
-- inviter_status: 0->等待验证，1->同意，2->拒绝，3->删除
-- invitee_status: 0->等待验证，1->同意，2->拒绝，3->删除，4->忽略
CREATE TABLE IF NOT EXISTS chat_invitation (
    id             INT        UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    inviter_id     INT        UNSIGNED NOT NULL           COMMENT '邀请者ID',
    invitee_id     INT        UNSIGNED NOT NULL           COMMENT '受邀者ID',
    chatroom_id    INT        UNSIGNED NOT NULL           COMMENT '聊天室ID',
    inviter_status TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '邀请者状态',
    invitee_status TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '受邀者状态',
    create_time    BIGINT     UNSIGNED NOT NULL,
    update_time    BIGINT     UNSIGNED NOT NULL,
    FOREIGN KEY (inviter_id)  REFERENCES user(id)     ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (invitee_id)  REFERENCES user(id)     ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (chatroom_id) REFERENCES chatroom(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;