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

// 测试路由
Route::get('/', function () {
    return '<span style="font-size: 150px; font-weight: bolder; position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%); letter-spacing: -7.5px;">Running Successfully!</span>';
});

// 应用主路由/公共路由/杂项路由
Route::group('index', function () {
    Route::get('/', 'index');
    Route::get('imagecaptcha', 'imageCaptcha');
    Route::get('checkemail', 'checkEmail');
    Route::get('checkusername', 'checkUsername');

    Route::post('emailcaptcha', 'sendEmailCaptcha');
})->prefix('Index/');
