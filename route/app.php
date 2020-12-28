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



Route::get('phpinfo', function () {
    return phpinfo();
});



/** 应用主路由/公共路由/杂项路由 */
Route::group('index', function () {
    Route::get('$', 'index');
    Route::get('captcha', 'captcha');
})->completeMatch()->prefix('Index/');



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
        Route::get('$', 'getChatList');

        Route::put('sticky/:id', 'sticky');
        Route::put('unsticky/:id', 'unsticky');
        Route::put('readed/:id', 'readed');
        Route::put('unread/:id', 'unread');
    });

    Route::group('chatrooms', function () {
        Route::get('$', 'getChatrooms');
        Route::get('private', 'getPrivateChatrooms');
    });
})->completeMatch()->prefix('User/');



Route::group('user/:id', function () {
    Route::get('$', 'getUserById');
})->completeMatch()->prefix('User/');



/** 聊天室模块路由 */
Route::group('chatroom', function () {
    // Route::post('create', 'create');
})->completeMatch()->prefix('Chatroom/');

Route::group('chatroom/:id', function () {
    Route::get('$', 'getChatroom');
    Route::get('name', 'getName');
    Route::get('records/:msgId', 'getRecords');
    Route::get('members', 'getChatMembers');
})->completeMatch()->prefix('Chatroom/');



/** 好友模块路由 */
Route::group('friend', function () {
    Route::get('request/:id', 'getFriendRequestById');
    Route::get('request/self/:selfId', 'getFriendRequestBySelfId');
    Route::get('request/target/:targetId', 'getFriendRequestByTargetId');

    Route::get('requests/receive', 'getReceiveFriendRequests');
    Route::get('requests/send', 'getSendFriendRequests');

    Route::put('alias/:chatroomId', 'setFriendAlias');
})->completeMatch()->prefix('Friend/');



Route::group('friend/:id', function () {
    Route::get('isfriend', 'isFriend');
})->completeMatch()->prefix('Friend/');
