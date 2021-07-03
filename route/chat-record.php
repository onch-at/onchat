<?php

use app\middleware\Auth;
use app\middleware\ChatMember;
use app\middleware\ImageFile;
use app\middleware\VoiceFile;
use think\facade\Route;

Route::group('chat-record', function () {
    Route::group(function () {
        Route::get('records/<chatroomId>', 'getRecords');

        Route::post('image/<chatroomId>', 'image')->middleware(ImageFile::class);
        Route::post('voice/<chatroomId>', 'voice')->middleware(VoiceFile::class);
    })->middleware(ChatMember::class, 'chatroomId');
})->prefix('ChatRecord/')->middleware(Auth::class);;
