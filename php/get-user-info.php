<?php
require_once '../vendor/autoload.php';

use hypergo\user\User;

if(User::checkLogin()) {
    $info = json_decode($_SESSION["login_info"]);
    $infoList = User::getUserInfo($info->uid, [
        "nickname",
        "signature",
        "mood",
        "birthday",
        "sex",
        "constellation",
        "email"
    ]);
    exit(json_encode([
        "uid"           => $info->uid,
        "username"      => $info->username,
        "nickname"      => $infoList["nickname"],
        "signature"     => $infoList["signature"],
        "mood"          => $infoList["mood"],
        "birthday"      => $infoList["birthday"],
        "sex"           => $infoList["sex"],
        "constellation" => $infoList["constellation"],
        "email"         => $infoList["email"]
    ]));
} else {
    exit(json_encode(false));
}
?>