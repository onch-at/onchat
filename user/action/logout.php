<?php
require_once '../../vendor/autoload.php';

use hypergo\user\User;

User::logout();

exit("
  <script>
    location.href='../login'; //回到登录页面
  </script>
");
?>