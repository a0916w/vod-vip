<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll';
    protected $description = 'Start Telegram Bot long-polling (开发环境使用)';

    public function handle(TelegramBotService $bot): int
    {
        $me = $bot->getMe();
        $botName = $me['result']['username'] ?? 'Unknown';

        $this->info("🤖 Bot @{$botName} 已启动，开始轮询...");
        $this->info('按 Ctrl+C 停止');
        $this->newLine();

        // 先删除可能存在的 Webhook，避免冲突
        $bot->deleteWebhook();

        $offset = 0;

        while (true) {
            try {
                $updates = $bot->getUpdates($offset, 30);

                foreach ($updates as $update) {
                    $offset = $update['update_id'] + 1;

                    $from = $update['message']['from']['username'] ?? $update['message']['from']['id'] ?? '?';
                    $type = 'text';
                    if (isset($update['message']['video'])) $type = '🎬 video';
                    elseif (isset($update['message']['photo'])) $type = '🖼 photo';
                    elseif (isset($update['message']['document'])) $type = '📄 document';

                    $this->line("[" . now()->format('H:i:s') . "] @{$from} → {$type}");

                    $bot->handleUpdate($update);
                }
            } catch (\Throwable $e) {
                $this->error("轮询异常：{$e->getMessage()}");
                $this->warn('5 秒后重试...');
                sleep(5);
            }
        }

        return self::SUCCESS;
    }
}
