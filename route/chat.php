<?php

use think\facade\Route;

/** 聊天室模块路由 */
Route::group('chat', function () {
    Route::get('requests/receive', 'getReceiveRequests');
})->completeMatch()->prefix('Chat/');
