<?php
require_once '../vendor/autoload.php';

use hypergo\user\User;

if(User::checkLogin()) {
    $info = json_decode($_SESSION["login_info"]);

    exit(json_encode($info->username));
} else {
    exit(json_encode(""));
}
?>