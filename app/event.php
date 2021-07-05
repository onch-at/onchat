<?php

use app\listener\task\ClearChatRequest;
use app\listener\task\ClearFriendRequest;

return [
    'bind'      => [],

    'listen'    => [
        'AppInit'            => [],
        'HttpRun'            => [],
        'HttpEnd'            => [],
        'LogLevel'           => [],
        'LogWrite'           => [],
        'swoole.workerStart' => [ClearFriendRequest::class, ClearChatRequest::class],
    ],

    'subscribe' => [],
];
