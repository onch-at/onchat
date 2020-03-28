<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

/** 测试路由 */
Route::get('$', function () {
    return '<span style="font-size: 150px; font-weight: bolder; position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%); letter-spacing: -7.5px;">Running Successfully!</span>';
});

/** 应用主路由/公共路由 */
Route::group('index', function () {
    Route::get('', 'index');
    Route::get('captcha', 'captcha');
})->completeMatch()->prefix('Index/');

/** 用户模块路由 */
Route::group('user', function () {
    Route::get('logout', 'logout');
    Route::get('checkLogin', 'checkLogin');
    Route::get('chatrooms', 'getChatrooms');
    Route::get('chatlist', 'getChatList');
    Route::post('login', 'login');
    Route::post('register', 'register');
})->completeMatch()->prefix('User/');