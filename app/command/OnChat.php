<?php

declare(strict_types=1);

namespace app\command;

use app\facade\ChatroomService;
use app\utils\File as FileUtils;
use app\utils\Str as StrUtils;
use think\console\input\Argument;
use think\console\input\Option;
use think\facade\Config;
use think\swoole\command\Server as ServerCommand;
use think\swoole\Manager;

class OnChat extends ServerCommand
{
    const ACTION_START = 'start';
    const ACTION_INIT = 'init';

    public function configure()
    {
        // 指令配置
        $this->setName('onchat')
            ->addArgument('action', Argument::OPTIONAL, 'start|init', self::ACTION_START)
            ->addOption(
                'env',
                'E',
                Option::VALUE_REQUIRED,
                'Environment name',
                ''
            )
            ->setDescription('OnChat Application');
    }

    public function handle(Manager $manager)
    {
        $this->output->info('OnChat: Starting execution…');

        $action = trim($this->input->getArgument('action'));

        switch ($action) {
            case self::ACTION_START:
                parent::handle($manager);
                break;

            case self::ACTION_INIT:
                $this->init();
                break;

            default:
                $this->output->error('OnChat: Unknown action!');
        }

        $this->output->info('OnChat: Execution finished!');
    }

    protected function init()
    {
        $default = Config::get('database.default');
        $config = Config::get('database.connections.' . $default);
        $host = $config['hostname'];
        $port = $config['hostport'];
        $username = $config['username'];
        $password = $config['password'];
        $database = $config['database'];

        $sql = FileUtils::read(resource_path('sql') . 'database.sql'); // 创建数据库的SQL

        $this->output->comment('Connecting to database…');

        $dbh = new \PDO("mysql:host={$host};port={$port}", $username, $password);

        $this->output->comment('Attempting to create database: ' . $database);
        $dbh->exec(StrUtils::assign($sql, ['database' => $database]));

        $path = resource_path('sql/table');
        $dir = scandir($path);
        $dir = array_filter($dir, function ($file) {
            return preg_match('/(.sql)$/', $file);
        });

        // 对文件列表进行排序，将user，chatroom数据表排到前面，因为这些数据表被其他表所依赖
        usort($dir, function ($a, $b) {
            return in_array($b, ['user.sql', 'chatroom.sql']) ? 1 : 0;
        });

        foreach ($dir as $file) {
            $sql = FileUtils::read($path . $file);

            switch ($file) {
                case 'chat-record.sql': // 如果是消息记录表，则需要生成分表
                    for ($i = 0; $i < 10; $i++) {
                        $dbh->exec(StrUtils::assign($sql, ['index' => $i]));
                    }
                    break;

                default:
                    $dbh->exec($sql);
            }

            $this->output->comment('Execute SQL statement > ' . $file);
        }

        // 如果没有第一个聊天室，那么就创建一个吧！
        if (ChatroomService::getChatroom(1)->isError()) {
            ChatroomService::creatChatroom('OnChat');
        }
    }
}
