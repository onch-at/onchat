<?php
require_once '../../vendor/autoload.php';

use hypergo\user\User;
use hypergo\utils\Session;

if(User::checkLogin()) {
    $info = json_decode($_SESSION["login_info"]);
    User::setUserInfo($info->uid, $_POST);
    
    exit(json_encode(true));
} else {
    exit(json_encode(false));
}
?>