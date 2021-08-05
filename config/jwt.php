<?php

// +----------------------------------------------------------------------
// | JWT设置
// +----------------------------------------------------------------------

return [
    // token name
    'name' => 'access_token',
    // Issuer 发行人
    'iss'  => env('jwt.iss', 'api.chat.hypergo.net'),
    // Audience 观众
    'aud'  => env('jwt.aud', 'chat.hypergo.net')
];
