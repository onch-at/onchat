<?php

declare(strict_types=1);

namespace app\table;

class Fd extends Table
{
    protected $name = 'fd';

    public function set(int $userId, string $fd): bool
    {
        return $this->table->set((string) $userId, [
            'fd' => $fd,
        ]);
    }

    public function getFd(int $userId)
    {
        return $this->get($userId, 'fd');
    }
}
