<?php

use app\middleware\Auth;
use think\facade\Route;

/** 好友模块路由 */
Route::group('friend', function () {
    Route::get('request/<id>', 'getRequestById');
    Route::get('request/requester/<requesterId>', 'getRequestByRequesterId');
    Route::get('request/target/<targetId>', 'getRequestByTargetId');

    Route::get('requests/receive', 'getReceiveRequests');
    Route::get('requests/send', 'getSendRequests');

    Route::put('alias/<chatroomId>', 'setFriendAlias');
    Route::put('request/receive/readed/<id>', 'readedReceiveRequest');
    Route::put('request/send/readed/<id>', 'readedSendRequest');
})->completeMatch()->prefix('Friend/')->middleware(Auth::class);

Route::group('friend/<id>', function () {
    Route::get('isfriend', 'isFriend');
})->completeMatch()->prefix('Friend/')->middleware(Auth::class);
