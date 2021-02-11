<?php

declare(strict_types=1);

namespace app\listener\task;

use think\Event;
use app\core\Job;
use Swoole\Server\Task;

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
    public function handle(Task $task, Event $event)
    {
        if ($task->data instanceof Job) {
            $event->trigger('swoole.task.' . $task->data->name, $task->data->params);
        }
    }
}
