<?php
require_once '../vendor/autoload.php';

use hypergo\utils\Session;

Session::start();

if(!isset($_SESSION["login_info"])) exit(json_encode(["username" => ""]));

$info = json_decode($_SESSION["login_info"]);

exit(json_encode(["username" => $info->username]));
?>