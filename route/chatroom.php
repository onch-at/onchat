<?php

use app\middleware\Auth;
use app\middleware\Avatar;
use app\middleware\ChatMember;
use app\middleware\ImageFile;
use app\middleware\VoiceFile;
use think\facade\Route;

// 聊天室模块路由
Route::group('chatroom', function () {
    // Route::post('create', 'create');
})->completeMatch()->prefix('Chatroom/');

Route::group('chatroom/<id>', function () {
    Route::get('/', 'getChatroom');

    Route::post('avatar', 'avatar')->middleware(Avatar::class);

    Route::put('name', 'setName');

    Route::group(function () {
        Route::get('name', 'getName');
        Route::get('records/<msgId>', 'getRecords');
        Route::get('members', 'getChatMembers');

        Route::post('image', 'image')->middleware(ImageFile::class);
        Route::post('voice', 'voice')->middleware(VoiceFile::class);

        Route::put('member/nickname', 'setNickname');
    })->middleware(ChatMember::class);
})->completeMatch()->prefix('Chatroom/')->middleware(Auth::class);
