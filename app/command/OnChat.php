<?php

declare(strict_types=1);

namespace app\command;

use app\core\Result;
use app\facade\ChatroomService;
use app\util\Str as StrUtil;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\facade\Console;
use think\facade\Db;

class OnChat extends Command
{
    const ACTION_START   = 'start';
    const ACTION_STOP    = 'stop';
    const ACTION_RESTART = 'restart';
    const ACTION_RELOAD  = 'reload';
    const ACTION_INSTALL = 'install';

    protected function configure()
    {
        // 指令配置
        $this->setName('onchat')
            ->addArgument('action', Argument::OPTIONAL, 'start|stop|restart|reload|install', self::ACTION_START)
            ->setDescription('OnChat Application');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->info('OnChat: Starting execution…');

        $action = trim($input->getArgument('action'));

        switch ($action) {
            case self::ACTION_START:
            case self::ACTION_STOP:
            case self::ACTION_RESTART:
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
        }

        $output->info('OnChat: Execution finished!');
    }

    protected function install($output)
    {
        $path = resource_path('sql/table');

        $dir = scandir($path);

        $output->comment('Execute SQL statement…');
        foreach ($dir as $file) {
            $filename = $path . $file;
            if (preg_match('/(.sql)$/', $file) && is_file($filename)) {
                $sql = file_get_contents($filename);

                switch ($file) {
                    case 'chat-record.sql': // 如果是消息记录表，则需要生成分表
                        for ($i = 0; $i < 10; $i++) {
                            Db::execute(StrUtil::assign($sql, ['index' => $i]));
                        }
                        break;

                    default:
                        Db::execute($sql);
                }

                $output->comment(' > ' . $file);
            }
        }

        $result = ChatroomService::getChatroom(1);
        // 如果没有第一个聊天室，那么就创建一个吧！
        if (!$result->isSuccess()) {
            ChatroomService::creatChatroom('OnChat');
        }
    }
}
