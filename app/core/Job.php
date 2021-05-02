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
     * @param string $class 任务类名
     * @param array $params 任务参数
     */
    public function __construct(string $class, array $params = null)
    {
        $this->name   = (new \ReflectionClass($class))->getShortName();
        $this->params = $params;
    }
}
