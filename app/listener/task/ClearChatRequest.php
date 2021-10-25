<?php

declare(strict_types=1);

namespace app\listener\task;

use app\model\ChatRequest;
use Swoole\Timer;
use think\swoole\Manager;

class ClearChatRequest
{
    /**
     * 事件监听处理.
     *
     * @return mixed
     */
    public function handle(Manager $manager)
    {
        if ($manager->getWorkerId() === 0) {
            Timer::tick(86400 * 1000, function () {
                // 清理过期的入群申请（30天）
                ChatRequest::where('update_time', '<', (time() - 86400 * 30) * 1000)->delete();
            });
        }
    }
}
