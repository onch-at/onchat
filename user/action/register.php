<?php
require_once '../../vendor/autoload.php';

use hypergo\user\User;
use hypergo\utils\Session;

header("Content-Type: application/json; charset=utf-8");

Session::start();

function sendJsonData($code, $msg = "") {
    $data = [
        "status_code" => $code,
        "error_msg" => $msg
    ];
    
    exit(json_encode($data));
}

if (strtolower($_POST["captcha"]) == $_SESSION["captcha"] and !empty($_POST["captcha"])) { //如果验证码正确且验证码不为空（防止绕过验证码）
    $user = new User("{$_POST["username"]}", "{$_POST["password"]}");
    $register = $user->register();
    sendJsonData($register, $user->getErrorMessage());
} else {
    sendJsonData(-1);
}

?>