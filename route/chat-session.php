<?php

use app\middleware\Auth;
use think\facade\Route;

Route::group('chatsession', function () {
  Route::get('/', 'getChatSessions');

  Route::put('sticky/<id>', 'stickyChatSession');
  Route::put('unsticky/<id>', 'unstickyChatSession');
  Route::put('readed/<id>', 'readedChatSession');
  Route::put('unread/<id>', 'unreadChatSession');
})->prefix('ChatSession/')->middleware(Auth::class);
