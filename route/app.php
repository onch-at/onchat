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

Route::group('index', function () {
    Route::get('captcha', 'captcha');
    Route::get('index', 'index');
})->completeMatch()->prefix('Index/');

Route::group('user', function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
})->completeMatch()->prefix('User/');