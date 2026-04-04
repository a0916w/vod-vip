<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook {action : set 或 delete} {--url= : Webhook URL (set 时必填)}';
    protected $description = '管理 Telegram Bot Webhook';

    public function handle(TelegramBotService $bot): int
    {
        $action = $this->argument('action');

        if ($action === 'set') {
            $url = $this->option('url') ?: config('telegram.webhook_url');
            if (! $url) {
                $this->error('请通过 --url 参数或 TELEGRAM_WEBHOOK_URL 配置指定 Webhook 地址');
                return self::FAILURE;
            }

            $result = $bot->setWebhook($url);
            if ($result['ok'] ?? false) {
                $this->info("✅ Webhook 设置成功：{$url}");
            } else {
                $this->error('❌ 设置失败：' . ($result['description'] ?? '未知错误'));
            }
        } elseif ($action === 'delete') {
            $result = $bot->deleteWebhook();
            if ($result['ok'] ?? false) {
                $this->info('✅ Webhook 已删除');
            } else {
                $this->error('❌ 删除失败');
            }
        } else {
            $this->error('action 参数只能是 set 或 delete');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
