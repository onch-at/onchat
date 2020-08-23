<?php

declare(strict_types=1);

namespace app\command;

use think\facade\Cache;
use think\console\Input;
use think\console\Output;
use think\facade\Console;
use think\console\Command;
use think\console\input\Argument;
use app\listener\websocket\BaseListener;

class OnChat extends Command
{
    const ACTION_START   = 'start';
    const ACTION_STOP    = 'stop';
    const ACTION_RESTART = 'restart';
    const ACTION_RELOAD  = 'reload';

    protected function configure()
    {
        // 指令配置
        $this->setName('onchat')
            ->addArgument('action', Argument::OPTIONAL, 'start|stop|restart|reload', self::ACTION_START)
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
        $output->info('OnChat: Starting execution…');

        $action = trim($input->getArgument('action'));

        switch ($action) {
            case self::ACTION_START:
            case self::ACTION_STOP:
            case self::ACTION_RESTART:
                $this->clearCache();
                Console::call('swoole', [$action]);
                break;

            case self::ACTION_RELOAD:
                Console::call('swoole', [$action]);
                break;

            default:
                $output->error('OnChat: Unknown action!');
                break;
        }

        $output->info('OnChat: Execution finished!');
    }
}
