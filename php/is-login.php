<?php
require_once '../vendor/autoload.php';

use hypergo\utils\Session;

Session::start();

if(empty($_SESSION["login_info"])) {
    exit(json_encode(false));
} else {
    exit(json_encode(true));
}
?>