<?php

use think\facade\Route;

/** 用户模块路由 */
Route::group('user', function () {
    Route::get('id', 'getUserId');
    Route::get('logout', 'logout');
    Route::get('checklogin', 'checkLogin');

    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('avatar', 'avatar');

    Route::put('info', 'saveUserInfo');

    Route::group('chatsession', function () {
        Route::get('/', 'getChatSessions');

        Route::put('sticky/<id>', 'stickyChatSession');
        Route::put('unsticky/<id>', 'unstickyChatSession');
        Route::put('readed/<id>', 'readedChatSession');
        Route::put('unread/<id>', 'unreadChatSession');
    });

    Route::group('chatrooms', function () {
        Route::get('/', 'getChatrooms');
        Route::get('private', 'getPrivateChatrooms');
    });
})->completeMatch()->prefix('User/');

Route::group('user/<id>', function () {
    Route::get('/', 'getUserById');
})->completeMatch()->prefix('User/');
