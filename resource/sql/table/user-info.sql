-- 用户信息表
CREATE TABLE IF NOT EXISTS user_info (
    id               INT          UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id          INT          UNSIGNED NOT NULL COMMENT '用户ID',
    nickname         VARCHAR(30)           NOT NULL COMMENT '昵称',
    signature        VARCHAR(200)              NULL COMMENT '个性签名',
    mood             TINYINT(1)   UNSIGNED     NULL COMMENT '心情',
    login_time       BIGINT       UNSIGNED NOT NULL COMMENT '登录时间',
    birthday         BIGINT       UNSIGNED     NULL COMMENT '生日',
    gender           TINYINT(1)   UNSIGNED     NULL COMMENT '性别',
    age              TINYINT(1)   UNSIGNED     NULL COMMENT '年龄',
    constellation    TINYINT(1)   UNSIGNED     NULL COMMENT '星座',
    avatar           VARCHAR(256)          NOT NULL COMMENT '头像',
    background_image VARCHAR(256)          NOT NULL COMMENT '背景图',
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;