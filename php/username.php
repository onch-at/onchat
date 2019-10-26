<?php
require_once '../vendor/autoload.php';

use hypergo\user\User;

exit(User::getUsernameByUid((int) $_GET["uid"]));
?>