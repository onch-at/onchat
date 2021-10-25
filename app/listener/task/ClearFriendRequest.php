<?php

declare(strict_types=1);

namespace app\listener\task;

use app\model\FriendRequest;
use Swoole\Timer;
use think\swoole\Manager;

class ClearFriendRequest
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
                // 清理过期的好友申请（30天，并且双方都已读）
                FriendRequest::where('update_time', '<', (time() - 86400 * 30) * 1000)->delete();
            });
        }
    }
}
