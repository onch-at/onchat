<?php
require_once '../../vendor/autoload.php';

use hypergo\user\User;
use hypergo\utils\Session;

header("Content-Type: application/json; charset=utf-8");

Session::start();

$user = new User($_POST["username"], $_POST["password"]);
$login = $user->login();

function sendJsonData($code, $msg = "") {
  $data = [
    "status_code" => $code,
    "error_msg" => $msg
  ];
  
  exit(json_encode($data));
}

sendJsonData($login, $user->getErrorMessage());
?>