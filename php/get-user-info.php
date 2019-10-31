<?php
require_once '../vendor/autoload.php';

use hypergo\user\User;

if(User::checkLogin()) {
    $info = json_decode($_SESSION["login_info"]);

    exit(json_encode([
        "uid" => $info->uid,
        "username" => $info->username,
        "nickname" => "我的昵称",
        "signature" => "这个人很懒，什么都没留下……"
    ]));
} else {
    exit(json_encode(false));
}
?>