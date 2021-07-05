<?php

use app\middleware\Auth;
use think\facade\Route;

Route::group('chatsession', function () {
  Route::get('/', 'getChatSessions');

  Route::put('sticky/<id>', 'sticky');
  Route::put('unsticky/<id>', 'unsticky');
  Route::put('readed/<id>', 'readed');
  Route::put('unread/<id>', 'unread');
  Route::put('hide/<id>', 'hide');
})->prefix('ChatSession/')->middleware(Auth::class);
