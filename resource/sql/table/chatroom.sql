-- 聊天室表
CREATE TABLE IF NOT EXISTS chatroom (
    id             INT          UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(60)               NULL COMMENT '聊天室名字',
    description    VARCHAR(600)              NULL COMMENT '聊天室描述',
    avatar         VARCHAR(256)              NULL COMMENT '聊天室头像URL',
    type           TINYINT(1)   UNSIGNED NOT NULL COMMENT '聊天室的类型',
    max_people_num INT          UNSIGNED NOT NULL COMMENT '聊天室最大人数',
    create_time    BIGINT       UNSIGNED NOT NULL,
    update_time    BIGINT       UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;