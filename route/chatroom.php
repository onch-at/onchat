<?php

use think\facade\Route;
use app\middleware\Auth;

/** 聊天室模块路由 */
Route::group('chatroom', function () {
    // Route::post('create', 'create');
})->completeMatch()->prefix('Chatroom/');

Route::group('chatroom/<id>', function () {
    Route::get('/', 'getChatroom');
    Route::get('name', 'getName');
    Route::get('records/<msgId>', 'getRecords');
    Route::get('members', 'getChatMembers');

    Route::post('avatar', 'avatar');
})->completeMatch()->prefix('Chatroom/')->middleware(Auth::class);
