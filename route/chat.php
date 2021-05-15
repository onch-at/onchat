<?php

use app\middleware\Auth;
use think\facade\Route;

// 聊天室管理相关模块路由
Route::group('chat', function () {
    Route::get('requests/receive', 'getReceiveRequests');
    Route::get('requests/receive/<id>', 'getReceiveRequestById');
    Route::get('requests/send', 'getSendRequests');
    Route::get('requests/send/<id>', 'getSendRequestById');

    Route::put('requests/readed', 'readed');
})->prefix('Chat/')->middleware(Auth::class);
