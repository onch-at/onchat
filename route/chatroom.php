<?php

use app\middleware\Auth;
use app\middleware\Avatar;
use app\middleware\ChatManager;
use app\middleware\ChatMember;
use think\facade\Route;

// 聊天室模块路由
Route::group('chatroom', function () {
    // Route::post('create', 'create');
})->prefix('Chatroom/');

Route::group('chatroom/<id>', function () {
    Route::get('/', 'getChatroom');
    Route::get('members', 'getChatMembers');

    Route::group(function () {
        Route::post('avatar', 'avatar')->middleware(Avatar::class);

        Route::put('name', 'setName');
    })->middleware(ChatManager::class);

    Route::group(function () {
        Route::get('name', 'getName');

        Route::put('member/nickname', 'setNickname');
    })->middleware(ChatMember::class);
})->prefix('Chatroom/')->middleware(Auth::class);
