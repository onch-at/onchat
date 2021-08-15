<?php

declare(strict_types=1);

namespace app\table;

use app\contract\Table;

class TokenExpire extends Table
{
    protected $name = 'token-expire';

    public function set(string $fd, int $expire): bool
    {
        return $this->table->set($fd, [
            'expire' => $expire
        ]);
    }
}