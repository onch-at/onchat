<?php

use think\facade\Route;
use app\middleware\Auth;

/** 用户模块路由 */
Route::group('user', function () {
    Route::get('checklogin', 'checkLogin');

    Route::post('login', 'login');
    Route::post('register', 'register');
})->completeMatch()->prefix('User/');

Route::group('user', function () {
    Route::get('logout', 'logout');

    Route::post('avatar', 'avatar');

    Route::put('info', 'saveUserInfo');

    Route::group('chatrooms', function () {
        Route::get('private', 'getPrivateChatrooms');
        Route::get('group', 'getGroupChatrooms');
    });

    Route::group('chatsession', function () {
        Route::get('/', 'getChatSessions');

        Route::put('sticky/<id>', 'stickyChatSession');
        Route::put('unsticky/<id>', 'unstickyChatSession');
        Route::put('readed/<id>', 'readedChatSession');
        Route::put('unread/<id>', 'unreadChatSession');
    });
})->completeMatch()->prefix('User/')->middleware(Auth::class);

Route::group('user/<id>', function () {
    Route::get('/', 'getUserById');
})->completeMatch()->prefix('User/')->middleware(Auth::class);
