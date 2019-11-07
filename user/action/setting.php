<?php
if (empty($_POST)) exit(json_encode(false));
require_once '../../vendor/autoload.php';

use hypergo\user\User;
use hypergo\utils\Session;

if (User::checkLogin()) {
    foreach ($_POST as $field => $value) {
        switch ($field) {
            case "nickname":
                if (mb_strlen($value, "utf-8") > 30) exit(json_encode("nickname"));
                break;

            case "signature":
                if (mb_strlen($value, "utf-8") > 50) exit(json_encode("signature"));
                break;

            case "mood":
                if ($value < 1 and $value > 4) exit(json_encode("mood"));
                break;

            case "birthday":
                if (strtotime($value) == false) exit(json_encode("birthday"));
                break;

            case "sex":
                if ($value < 0 and $value > 2) exit(json_encode("sex"));
                break;

            case "constellation":
                if ($value < 1 and $value > 12) exit(json_encode("constellation"));
                break;

            case "email":
                if (mb_strlen($value, "utf-8") > 50 or !filter_var($value, FILTER_VALIDATE_EMAIL)) exit(json_encode("email"));
                break;

            default:
                unset($_POST[$field]); //剔除错误的field
                break;
        }
    }

    if (empty($_POST)) exit(json_encode(false)); //再次判断
    
    $info = json_decode($_SESSION["login_info"]);
    User::setUserInfo($info->uid, $_POST);
    
    exit(json_encode(true));
} else {
    exit(json_encode(false));
}
?>