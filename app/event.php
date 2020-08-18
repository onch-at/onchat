<?php
// 事件定义文件
return [
    'bind'      => [],

    'listen'    => [
        'AppInit'                              => [],
        'HttpRun'                              => [],
        'HttpEnd'                              => [],
        'LogLevel'                             => [],
        'LogWrite'                             => [],
        'swoole.websocket.Init'                => [app\listener\websocket\Init::class],
        'swoole.websocket.Unload'              => [app\listener\websocket\Unload::class],
        'swoole.websocket.Message'             => [app\listener\websocket\Message::class],
        'swoole.websocket.RevokeMsg'           => [app\listener\websocket\RevokeMsg::class],
        'swoole.websocket.FriendRequest'       => [app\listener\websocket\FriendRequest::class],
        'swoole.websocket.FriendRequestAgree'  => [app\listener\websocket\FriendRequestAgree::class],
        'swoole.websocket.FriendRequestReject' => [app\listener\websocket\FriendRequestReject::class],
        'swoole.websocket.Close'               => [app\listener\websocket\Unload::class],
    ],

    'subscribe' => [],
];
