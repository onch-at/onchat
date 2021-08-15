<?php

declare(strict_types=1);

namespace app\contract;

use think\Container;

abstract class Table
{
    /** 内存表名称 */
    protected $name;

    public $table;

    public function __construct(Container $container)
    {
        $this->table = $container->make('swoole.table.' . $this->name);
    }

    /**
     * 获取一行数据
     *
     * @param string|integer $key
     * @param string $field
     * @return mixed
     */
    public function get($key, string $field = null)
    {
        return $field ? $this->table->get((string) $key, $field) : $this->table->get((string) $key);
    }

    /**
     * 删除一行数据
     *
     * @param string|integer $key
     * @return boolean
     */
    public function del($key): bool
    {
        return $this->table->del((string) $key);
    }
}
