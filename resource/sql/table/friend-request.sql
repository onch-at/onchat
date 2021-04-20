-- 好友申请表
-- status: 0->等待验证，1->同意，2->拒绝
CREATE TABLE IF NOT EXISTS friend_request (
    id               INT         UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    requester_id     INT         UNSIGNED NOT NULL               COMMENT '申请人ID',
    target_id        INT         UNSIGNED NOT NULL               COMMENT '被申请人ID',
    request_reason   VARCHAR(50)              NULL               COMMENT '申请原因',
    reject_reason    VARCHAR(50)              NULL               COMMENT '拒绝理由',
    status           TINYINT(1)  UNSIGNED NOT NULL DEFAULT 0     COMMENT '申请状态',
    requester_readed BOOLEAN              NOT NULL DEFAULT TRUE  COMMENT '申请人已读',
    target_readed    BOOLEAN              NOT NULL DEFAULT FALSE COMMENT '被申请人已读',
    requester_alias  VARCHAR(30)              NULL               COMMENT '申请人的别名',
    target_alias     VARCHAR(30)              NULL               COMMENT '被申请人的别名',
    create_time      BIGINT      UNSIGNED NOT NULL,
    update_time      BIGINT      UNSIGNED NOT NULL,
    FOREIGN KEY (requester_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (target_id)    REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;