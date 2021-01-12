<?php

use app\middleware\Auth;
use think\facade\Route;

/** 聊天室模块路由 */
Route::group('chat', function () {
    Route::get('requests/receive', 'getReceiveRequests');

    Route::put('requests/readed', 'readed');
})->completeMatch()->prefix('Chat/')->middleware(Auth::class);
