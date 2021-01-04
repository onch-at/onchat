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

    Route::group('chatlist', function () {
        Route::get('/', 'getChatList');

        Route::put('sticky/<id>', 'sticky');
        Route::put('unsticky/<id>', 'unsticky');
        Route::put('readed/<id>', 'readed');
        Route::put('unread/<id>', 'unread');
    });

    Route::group('chatrooms', function () {
        Route::get('/', 'getChatrooms');
        Route::get('private', 'getPrivateChatrooms');
    });
})->completeMatch()->prefix('User/');

Route::group('user/<id>', function () {
    Route::get('/', 'getUserById');
})->completeMatch()->prefix('User/');
