<?php

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Console;
use app\core\handler\User as UserHandler;
use app\listener\websocket\BaseListener;
use think\facade\Cache;

class OnChat extends Command
{
    const ACTION_START = 'start';
    const ACTION_STOP = 'stop';
    const ACTION_RESTART = 'restart';

    protected function configure()
    {
        // 指令配置
        $this->setName('onchat')
            ->addArgument('action', Argument::OPTIONAL, 'start|stop|restart', self::ACTION_START)
            ->setDescription('the onchat command');
    }

    /**
     * 清理缓存
     *
     * @return void
     */
    public function clearCache()
    {
        $redis = Cache::store('redis')->handler();
        $redis->del(BaseListener::REDIS_HASH_FD_USER_PAIR);
        $redis->del(BaseListener::REDIS_HASH_UID_FD_PAIR);
    }

    protected function execute(Input $input, Output $output)
    {

        $action = trim($input->getArgument('action'));

        switch ($action) {
            case self::ACTION_START:
            case self::ACTION_STOP:
            case self::ACTION_RESTART:
                $this->clearCache();
                $output->info('OnChat: ' . $action . ' successful!');
                Console::call('swoole', [$action]);
                break;

            default:
                $output->error('OnChat: 未知指令动作');
                break;
        }
    }
}
