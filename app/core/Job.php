<?php

declare(strict_types=1);

namespace app\core;

class Job
{
    public $name;

    public $params;

    /**
     * 工作
     *
     * @param string $name 任务名
     * @param array $params 任务参数
     */
    public function __construct(string $name, array $params = null)
    {
        $this->name   = $name;
        $this->params = $params;
    }
}
