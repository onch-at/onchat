-- 用户表
CREATE TABLE IF NOT EXISTS user (
    id          INT          UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(30)           NOT NULL UNIQUE KEY COMMENT '用户名',
    password    VARCHAR(256)          NOT NULL            COMMENT '密码',
    email       VARCHAR(50)               NULL UNIQUE KEY COMMENT '电子邮箱',
    telephone   CHAR(11)                  NULL UNIQUE KEY COMMENT '电话号码',
    create_time BIGINT       UNSIGNED NOT NULL,
    update_time BIGINT       UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;