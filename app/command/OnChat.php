<?php

declare(strict_types=1);

namespace app\command;

use think\facade\Db;
use think\facade\Cache;
use think\console\Input;
use think\console\Output;
use think\facade\Console;
use think\console\Command;
use think\console\input\Argument;
use app\listener\websocket\BaseListener;

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
                $rootPath = root_path();
                $sqls = [
                    'user'           => file_get_contents($rootPath . '/resource/sql/user.sql'),
                    'user_info'      => file_get_contents($rootPath . '/resource/sql/user-info.sql'),
                    'chatroom'       => file_get_contents($rootPath . '/resource/sql/chatroom.sql'),
                    'chat_member'    => file_get_contents($rootPath . '/resource/sql/chat-member.sql'),
                    'friend_request' => file_get_contents($rootPath . '/resource/sql/friend-request.sql'),
                    // 'chat_record' =>  file_get_contents('./resource/sql/chat-record.sql'),
                ];

                $output->comment('  Creating table…');
                foreach ($sqls as $table => $sql) {
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
                break;

            default:
                $output->error('OnChat: Unknown action!');
                break;
        }

        $output->info('OnChat: Execution finished!');
    }
}
