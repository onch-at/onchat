<?php

use app\middleware\Auth;
use think\facade\Route;

// 令牌认证相关模块路由
Route::group('auth', function () {
  Route::get('refresh', 'refresh');

  Route::group(function () {
    Route::get('info', 'info');
    Route::get('logout', 'logout');
  })->middleware(Auth::class);
})->prefix('Auth/');
