<?php
require_once "../../vendor/autoload.php";

use hypergo\user\User;

if (User::checkLogin()) { //如果已经登录，则不允许访问验证码
  http_response_code(403);
  exit();
}

use hypergo\utils\Captcha;
use hypergo\utils\Session;

Session::start();
$image = new Captcha(__DIR__ ."/fonts/". mt_rand(1, 5) .".ttf");

$image->setBackground();
$image->drawPixel();
$image->drawText();
$image->setCaptcha();
$image->drawCurve();
$image->outputPng();

$_SESSION["captcha"] = $image->getCaptcha();

$image->destroy();
?>