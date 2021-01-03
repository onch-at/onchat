-- 聊天申请表
-- status: 0->等待验证，1->同意，2->拒绝，3->删除
CREATE TABLE IF NOT EXISTS chat_request (
    id             INT         UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    chatroom_id    INT         UNSIGNED NOT NULL           COMMENT '聊天室ID',
    applicant_id   INT         UNSIGNED NOT NULL           COMMENT '申请人ID',
    status         TINYINT(1)  UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请状态',
    request_reason VARCHAR(50)              NULL           COMMENT '申请原因',
    reject_reason  VARCHAR(50)              NULL           COMMENT '拒绝理由',
    readed_list    JSON                 NOT NULL           COMMENT '已读列表',
    create_time    BIGINT      UNSIGNED NOT NULL,
    update_time    BIGINT      UNSIGNED NOT NULL,
    FOREIGN KEY (applicant_id) REFERENCES user(id)     ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (chatroom_id)  REFERENCES chatroom(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;