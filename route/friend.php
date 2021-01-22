<?php

use think\facade\Route;
use app\middleware\Auth;

/** 好友模块路由 */
Route::group('friend', function () {
    Route::get('request/<id>', 'getRequestById');
    Route::get('request/self/<selfId>', 'getRequestBySelfId');
    Route::get('request/target/<targetId>', 'getRequestByTargetId');

    Route::get('requests/receive', 'getReceiveRequests');
    Route::get('requests/send', 'getSendRequests');

    Route::put('alias/<chatroomId>', 'setFriendAlias');
})->completeMatch()->prefix('Friend/');



Route::group('friend/<id>', function () {
    Route::get('isfriend', 'isFriend');
})->completeMatch()->prefix('Friend/')->middleware(Auth::class);
