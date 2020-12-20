<?php

declare(strict_types=1);

namespace app\command;

use app\core\Result;
use think\facade\Db;
use think\facade\Cache;
use think\console\Input;
use think\console\Output;
use think\facade\Console;
use think\console\Command;
use think\console\input\Argument;
use app\listener\websocket\BaseListener;
use app\core\service\Chatroom as ChatroomService;

class OnChat extends Command
{
    const ACTION_START    = 'start';
    const ACTION_STOP     = 'stop';
    const ACTION_RESTART  = 'restart';
    const ACTION_RELOAD   = 'reload';
    const ACTION_INSTALL  = 'install';

    protected function configure()
    {
        // 指令配置
        $this->setName('onchat')
            ->addArgument('action', Argument::OPTIONAL, 'start|stop|restart|reload|install', self::ACTION_START)
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

            case self::ACTION_INSTALL:
                $this->install($output);
                break;

            default:
                $output->error('OnChat: Unknown action!');
                break;
        }

        $output->info('OnChat: Execution finished!');
    }

    private function install($output)
    {
        $path = root_path() . '/resource/sql';
        $sqls = [
            file_get_contents($path . '/table/user.sql'),
            file_get_contents($path . '/table/user-info.sql'),
            file_get_contents($path . '/table/chatroom.sql'),
            file_get_contents($path . '/table/chat-member.sql'),
            file_get_contents($path . '/table/friend-request.sql'),
            file_get_contents($path . '/table/chat-invitation.sql'),
        ];

        $output->comment('  Execute SQL statement…');
        foreach ($sqls as $sql) {
            Db::execute($sql);
        }

        $result = ChatroomService::getChatroom(1);
        // 如果没有第一个聊天室，那么就创建一个吧！
        if ($result->code !== Result::CODE_SUCCESS) {
            ChatroomService::creatChatroom('OnChat');
        }
    }
}
