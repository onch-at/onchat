<?php

declare(strict_types=1);

namespace app\table;

use app\constant\SessionKey;
use app\core\Redis;
use app\facade\FdTable;
use think\facade\Config;

class User extends Table
{
    protected $name = 'user';

    public function set(string $fd, string $sessId): bool
    {
        $redis = Redis::create();
        $sessPrefix = Config::get('session.prefix');
        $data = $redis->get($sessPrefix . $sessId);

        if (!$data) {
            return false;
        }

        $session = unserialize(unserialize($data));
        $userInfo = $session[SessionKey::USER_LOGIN];

        return $this->table->set($fd, [
            'id'       => $userInfo['id'],
            'username' => $userInfo['username']
        ]);
    }

    public function getByUserId(int $userId, string $field = null)
    {
        $fd = FdTable::getFd($userId);
        return $this->get($fd, $field);
    }
}
