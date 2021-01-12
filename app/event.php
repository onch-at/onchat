<?php

use app\listener\websocket\Init;
use app\listener\websocket\Unload;
use app\listener\websocket\Message;
use app\listener\websocket\RevokeMsg;
use app\listener\websocket\ChatRequest;
use app\listener\websocket\FriendRequest;
use app\listener\websocket\CreateChatroom;
use app\listener\websocket\FriendRequestAgree;
use app\listener\websocket\InviteJoinChatroom;
use app\listener\websocket\FriendRequestReject;

return [
    'bind'      => [],

    'listen'    => [
        'AppInit'                              => [],
        'HttpRun'                              => [],
        'HttpEnd'                              => [],
        'LogLevel'                             => [],
        'LogWrite'                             => [],
        'swoole.websocket.Init'                => [Init::class],
        'swoole.websocket.Unload'              => [Unload::class],
        'swoole.websocket.Message'             => [Message::class],
        'swoole.websocket.RevokeMsg'           => [RevokeMsg::class],
        'swoole.websocket.FriendRequest'       => [FriendRequest::class],
        'swoole.websocket.FriendRequestAgree'  => [FriendRequestAgree::class],
        'swoole.websocket.FriendRequestReject' => [FriendRequestReject::class],
        'swoole.websocket.CreateChatroom'      => [CreateChatroom::class],
        'swoole.websocket.InviteJoinChatroom'  => [InviteJoinChatroom::class],
        'swoole.websocket.ChatRequest'         => [ChatRequest::class],
        'swoole.websocket.Close'               => [Unload::class],
    ],

    'subscribe' => [],
];
