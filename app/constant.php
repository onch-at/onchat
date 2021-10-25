<?php

declare(strict_types=1);

/** 是否开放注册 */
define('ONCHAT_CAN_REGISTER', true);

/** 用户名最小长度 */
define('ONCHAT_USERNAME_MIN_LENGTH', 5);
/** 用户名最大长度 */
define('ONCHAT_USERNAME_MAX_LENGTH', 15);

/** 用户名正则表达式：字母/数字/中文/下划线/横杠 */
define('ONCHAT_USERNAME_PATTERN', "/^([a-z]|[A-Z]|[0-9]|_|-|[\x{4e00}-\x{9fa5}]){".ONCHAT_USERNAME_MIN_LENGTH.','.ONCHAT_USERNAME_MAX_LENGTH.'}$/u');

/** 邮箱最大长度 */
define('ONCHAT_EMAIL_MAX_LENGTH', 50);

/** 用户昵称最小长度 */
define('ONCHAT_NICKNAME_MIN_LENGTH', 1);
/** 用户昵称最大长度 */
define('ONCHAT_NICKNAME_MAX_LENGTH', 15);

/** 用户密码最小长度 */
define('ONCHAT_PASSWORD_MIN_LENGTH', 8);
/** 用户密码最大长度 */
define('ONCHAT_PASSWORD_MAX_LENGTH', 50);

/** 聊天室名称最小长度 */
define('ONCHAT_CHATROOM_NAME_MIN_LENGTH', 1);
/** 聊天室名称最大长度 */
define('ONCHAT_CHATROOM_NAME_MAX_LENGTH', 30);

/** 聊天室简介最小长度 */
define('ONCHAT_CHATROOM_DESCRIPTION_MIN_LENGTH', 5);
/** 聊天室简介最大长度 */
define('ONCHAT_CHATROOM_DESCRIPTION_MAX_LENGTH', 300);

/** 个性签名最小长度 */
define('ONCHAT_SIGNATURE_MIN_LENGTH', 1);
/** 个性签名最大长度 */
define('ONCHAT_SIGNATURE_MAX_LENGTH', 100);

/** 文本消息最长长度 */
define('ONCHAT_TEXT_MSG_MAX_LENGTH', 3000);

/** 申请/拒绝原因最长长度 */
define('ONCHAT_REASON_MAX_LENGTH', 50);

/** 用户创建群聊最大数量 */
define('ONCHAT_USER_MAX_GROUP_CHAT_COUNT', 10);

/** 访问令牌存活时间 */
define('ONCHAT_ACCESS_TOKEN_TTL', 3600);
/** 续签令牌存活时间 */
define('ONCHAT_REFRESH_TOKEN_TTL', 86400 * 15);
