<?php

declare(strict_types=1);

namespace app\listener\task;

use app\core\Job;
use Swoole\Server\Task;
use think\facade\Event;

/**
 * 任务分发器
 */
class TaskDispatcher
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle(Task $task)
    {
        if ($task->data instanceof Job) {
            Event::trigger('swoole.task.' . $task->data->name, $task->data->params);
        }
    }
}
