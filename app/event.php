<?php

use app\listener\task\SendMail;
use app\listener\websocket\Init;
use app\listener\websocket\Test;
use app\listener\websocket\Unload;
use app\listener\websocket\Message;
use app\listener\task\TaskDispatcher;
use app\listener\websocket\RevokeMsg;
use app\listener\websocket\ChatRequest;
use app\listener\websocket\FriendRequest;
use app\listener\websocket\CreateChatroom;
use app\listener\websocket\ChatRequestAgree;
use app\listener\websocket\ChatRequestReject;
use app\listener\websocket\FriendRequestAgree;
use app\listener\websocket\InviteJoinChatroom;
use app\listener\websocket\FriendRequestReject;
use app\listener\websocket\SocketEventDispatcher;

return [
    'bind'      => [],

    'listen'    => [
        'AppInit'                                    => [],
        'HttpRun'                                    => [],
        'HttpEnd'                                    => [],
        'LogLevel'                                   => [],
        'LogWrite'                                   => [],
        'swoole.websocket.Event'                     => [SocketEventDispatcher::class],
        'swoole.websocket.Event.Test'                => [Test::class],
        'swoole.websocket.Event.Init'                => [Init::class],
        'swoole.websocket.Event.Message'             => [Message::class],
        'swoole.websocket.Event.RevokeMsg'           => [RevokeMsg::class],
        'swoole.websocket.Event.FriendRequest'       => [FriendRequest::class],
        'swoole.websocket.Event.FriendRequestAgree'  => [FriendRequestAgree::class],
        'swoole.websocket.Event.FriendRequestReject' => [FriendRequestReject::class],
        'swoole.websocket.Event.CreateChatroom'      => [CreateChatroom::class],
        'swoole.websocket.Event.InviteJoinChatroom'  => [InviteJoinChatroom::class],
        'swoole.websocket.Event.ChatRequest'         => [ChatRequest::class],
        'swoole.websocket.Event.ChatRequestAgree'    => [ChatRequestAgree::class],
        'swoole.websocket.Event.ChatRequestReject'   => [ChatRequestReject::class],
        'swoole.websocket.Event.Unload'              => [Unload::class],
        'swoole.websocket.Event.Disconnect'          => [Unload::class],
        'swoole.websocket.Event.Close'               => [Unload::class],
        'swoole.task'                                => [TaskDispatcher::class],
        'swoole.task.SendMail'                       => [SendMail::class],
    ],

    'subscribe' => [],
];
