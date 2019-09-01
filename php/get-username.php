<?php
require_once '../vendor/autoload.php';

use hypergo\utils\Session;

Session::start();

if(empty($_SESSION["login_info"])) {
    exit(json_encode(""));
} else {
    $info = json_decode($_SESSION["login_info"]);

    exit(json_encode($info->username));
}
?>