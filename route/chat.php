<?php

use think\facade\Route;
use app\middleware\Auth;

/** 聊天室模块路由 */
Route::group('chat', function () {
    Route::get('requests/receive', 'getReceiveRequests');
    Route::get('requests/receive/<id>', 'getReceiveRequestById');

    Route::put('requests/readed', 'readed');
})->completeMatch()->prefix('Chat/')->middleware(Auth::class);
