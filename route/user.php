<?php

use app\middleware\Auth;
use app\middleware\AvatarImage;
use think\facade\Route;

/** 用户模块路由 */
Route::group('user', function () {
    Route::get('checklogin', 'checkLogin');
    Route::get('checkemail', 'checkEmail');

    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('emailcaptcha', 'sendEmailCaptcha');

    Route::put('password', 'changePassword');
    Route::put('password/reset', 'resetPassword');

    Route::group(function () {
        Route::get('logout', 'logout');

        Route::post('avatar', 'avatar')->middleware(AvatarImage::class);

        Route::put('info', 'saveUserInfo');
        Route::put('bindemail', 'bindEmail');

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
    })->middleware(Auth::class);
})->completeMatch()->prefix('User/');


Route::group('user/<id>', function () {
    Route::get('/', 'getUserById');
})->completeMatch()->prefix('User/')->middleware(Auth::class);
