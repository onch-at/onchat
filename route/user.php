<?php

use app\middleware\Auth;
use app\middleware\Avatar;
use think\facade\Route;

// 用户模块路由
Route::group('user', function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('emailcaptcha', 'sendEmailCaptcha');

    Route::put('password/reset', 'resetPassword');

    Route::group(function () {
        Route::post('avatar', 'avatar')->middleware(Avatar::class);
        Route::post('search', 'search');

        Route::put('info', 'saveUserInfo');
        Route::put('bindemail', 'bindEmail');
        Route::put('password', 'changePassword');

        Route::group('chatrooms', function () {
            Route::get('private', 'getPrivateChatrooms');
            Route::get('group', 'getGroupChatrooms');
        });
    })->middleware(Auth::class);
})->prefix('User/');

Route::group('user/<id>', function () {
    Route::get('/', 'getUserById');
})->prefix('User/')->middleware(Auth::class);
