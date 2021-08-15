<?php

declare(strict_types=1);

namespace app\table;

use app\contract\Table;
use app\entity\TokenPayload;

class User extends Table
{
    protected $name = 'user';

    public function set(string $fd, TokenPayload $payload): bool
    {
        return $this->table->set($fd, [
            'id'       => $payload->sub,
            'username' => $payload->usr->username
        ]);
    }
}
