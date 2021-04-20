<?php

declare(strict_types=1);

namespace app\listener\task;

use Swoole\Timer;
use app\model\FriendRequest;

class ClearFriendRequest
{

    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        Timer::tick(86400 * 1000, function () {
            // 清理过期的好友申请（30天，并且双方都已读）
            FriendRequest::where('update_time', '<', (time() - 86400 * 30) * 1000)
                ->where([
                    'requester_readed' => true,
                    'target_readed'   => true,
                ])->delete();
        });
    }
}
