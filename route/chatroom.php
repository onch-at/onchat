<?php

use app\middleware\Auth;
use app\middleware\ImageFile;
use think\facade\Route;

/** 聊天室模块路由 */
Route::group('chatroom', function () {
    // Route::post('create', 'create');
})->completeMatch()->prefix('Chatroom/');

Route::group('chatroom/<id>', function () {
    Route::get('/', 'getChatroom');
    Route::get('name', 'getName');
    Route::get('records/<msgId>', 'getRecords');
    Route::get('members', 'getChatMembers');

    Route::post('avatar', 'avatar')->middleware(ImageFile::class);;
})->completeMatch()->prefix('Chatroom/')->middleware(Auth::class);
