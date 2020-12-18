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
        $rootPath = root_path();
        $sqls = [
            file_get_contents($rootPath . '/resource/sql/table/user.sql'),
            file_get_contents($rootPath . '/resource/sql/table/user-info.sql'),
            file_get_contents($rootPath . '/resource/sql/table/chatroom.sql'),
            file_get_contents($rootPath . '/resource/sql/table/chat-member.sql'),
            file_get_contents($rootPath . '/resource/sql/table/friend-request.sql'),
            // 'chat_record' =>  file_get_contents('./resource/sql/chat-record.sql'),
        ];

        $output->comment('  Execute SQL statement…');
        foreach ($sqls as $sql) {
            Db::execute($sql);
        }

        $sql = function ($index) {
            return "CREATE TABLE IF NOT EXISTS chat_record_1_{$index} (
                        id          INT        UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        chatroom_id INT        UNSIGNED NOT NULL COMMENT '聊天室ID',
                        user_id     INT        UNSIGNED NULL     COMMENT '消息发送者ID',
                        type        TINYINT(1) UNSIGNED NOT NULL COMMENT '消息类型',
                        data        JSON                NOT NULL COMMENT '消息数据体',
                        reply_id    INT        UNSIGNED NULL     COMMENT '回复消息的消息记录ID',
                        create_time BIGINT     UNSIGNED NOT NULL,
                        FOREIGN KEY (chatroom_id) REFERENCES chatroom(id) ON DELETE CASCADE ON UPDATE CASCADE,
                        FOREIGN KEY (user_id)     REFERENCES user(id)     ON DELETE CASCADE ON UPDATE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        };

        for ($i = 0; $i < 100; $i++) {
            Db::execute($sql($i));
        }

        $result = ChatroomService::getChatroom(1);
        // 如果没有第一个聊天室，那么就创建一个吧！
        if ($result->code !== Result::CODE_SUCCESS) {
            ChatroomService::creatChatroom('OnChat');
        }
    }
}
