<?php

namespace App\Services;

use App\Models\MediaResource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TelegramBotService
{
    private string $token;
    private string $apiBase;
    private array $allowedUsers;

    public function __construct()
    {
        $this->token = config('telegram.bot_token');
        $this->apiBase = "https://api.telegram.org/bot{$this->token}";
        $this->allowedUsers = config('telegram.allowed_user_ids', []);
    }

    /**
     * 处理收到的 Update 消息
     */
    public function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (! $message) {
            return;
        }

        $fromId = $message['from']['id'] ?? null;
        $fromUsername = $message['from']['username'] ?? null;

        if (! empty($this->allowedUsers) && ! in_array($fromId, $this->allowedUsers)) {
            $this->sendMessage($message['chat']['id'], '⛔ 你没有权限使用此 Bot。');
            Log::warning("Telegram Bot: 非白名单用户尝试访问", ['user_id' => $fromId]);
            return;
        }

        $caption = $message['caption'] ?? null;

        if (isset($message['video'])) {
            $this->processMedia($message['video'], 'video', $caption, $fromId, $fromUsername, $message['chat']['id']);
        } elseif (isset($message['photo'])) {
            // photo 是数组，取最高分辨率（最后一个）
            $photo = end($message['photo']);
            $this->processMedia($photo, 'image', $caption, $fromId, $fromUsername, $message['chat']['id']);
        } elseif (isset($message['document'])) {
            $doc = $message['document'];
            $mimeType = $doc['mime_type'] ?? '';
            $fileType = str_starts_with($mimeType, 'video/') ? 'video'
                : (str_starts_with($mimeType, 'image/') ? 'image' : 'document');
            $this->processMedia($doc, $fileType, $caption, $fromId, $fromUsername, $message['chat']['id']);
        } elseif (isset($message['text'])) {
            $this->handleTextCommand($message);
        }
    }

    /**
     * 处理媒体文件：下载 + 入库
     */
    private function processMedia(
        array $fileInfo,
        string $fileType,
        ?string $caption,
        ?int $fromId,
        ?string $fromUsername,
        int $chatId,
    ): void {
        $fileId = $fileInfo['file_id'];
        $fileSize = $fileInfo['file_size'] ?? 0;

        // 20MB 限制提醒
        if ($fileSize > 20 * 1024 * 1024) {
            $this->sendMessage($chatId, '⚠️ 文件超过 20MB，标准 Bot API 无法下载。已记录文件 ID 供后续处理。');
            MediaResource::create([
                'telegram_file_id' => $fileId,
                'file_type' => $fileType,
                'file_name' => $fileInfo['file_name'] ?? null,
                'local_path' => 'pending_large_file',
                'file_size' => $fileSize,
                'caption' => $caption,
                'from_user_id' => $fromId,
                'from_username' => $fromUsername,
                'mime_type' => $fileInfo['mime_type'] ?? null,
                'duration' => $fileInfo['duration'] ?? null,
                'width' => $fileInfo['width'] ?? null,
                'height' => $fileInfo['height'] ?? null,
            ]);
            return;
        }

        // 防重复
        $exists = MediaResource::where('telegram_file_id', $fileId)->exists();
        if ($exists) {
            $this->sendMessage($chatId, '⏭ 该文件已入库，跳过。');
            return;
        }

        $this->sendMessage($chatId, '⏳ 正在下载...');

        try {
            // 获取文件路径
            $response = Http::get("{$this->apiBase}/getFile", ['file_id' => $fileId]);
            $filePath = $response->json('result.file_path');

            if (! $filePath) {
                $this->sendMessage($chatId, '❌ 获取文件路径失败。');
                return;
            }

            // 下载文件
            $downloadUrl = "https://api.telegram.org/file/bot{$this->token}/{$filePath}";
            $fileContents = Http::timeout(300)->get($downloadUrl)->body();

            // 生成存储路径
            $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: $this->guessExtension($fileType);
            $subDir = match ($fileType) {
                'video' => 'telegram/videos',
                'image' => 'telegram/images',
                default => 'telegram/documents',
            };
            $newName = date('Ymd_His') . '_' . Str::random(8) . '.' . $ext;
            $storagePath = "{$subDir}/{$newName}";

            Storage::disk('public')->put($storagePath, $fileContents);

            // 入库
            $resource = MediaResource::create([
                'telegram_file_id' => $fileId,
                'file_type' => $fileType,
                'file_name' => $fileInfo['file_name'] ?? $newName,
                'local_path' => $storagePath,
                'file_size' => $fileSize ?: strlen($fileContents),
                'caption' => $caption,
                'from_user_id' => $fromId,
                'from_username' => $fromUsername,
                'mime_type' => $fileInfo['mime_type'] ?? null,
                'duration' => $fileInfo['duration'] ?? null,
                'width' => $fileInfo['width'] ?? null,
                'height' => $fileInfo['height'] ?? null,
            ]);

            $sizeStr = $this->formatFileSize($resource->file_size);
            $typeEmoji = match ($fileType) {
                'video' => '🎬',
                'image' => '🖼',
                default => '📄',
            };

            $this->sendMessage($chatId, "{$typeEmoji} 入库成功！\n📁 {$resource->file_name}\n💾 {$sizeStr}\n🆔 #{$resource->id}");

            Log::info("Telegram Bot: 文件入库成功", [
                'id' => $resource->id,
                'type' => $fileType,
                'path' => $storagePath,
            ]);
        } catch (\Throwable $e) {
            $this->sendMessage($chatId, "❌ 下载失败：{$e->getMessage()}");
            Log::error("Telegram Bot: 下载失败", [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 处理文本命令
     */
    private function handleTextCommand(array $message): void
    {
        $text = trim($message['text']);
        $chatId = $message['chat']['id'];

        match (true) {
            str_starts_with($text, '/start') => $this->sendMessage($chatId,
                "👋 欢迎使用 VOD-VIP 资源采集 Bot！\n\n"
                . "直接向我发送或转发：\n"
                . "🎬 视频\n🖼 图片\n📄 文件\n\n"
                . "我会自动下载并入库。\n\n"
                . "可用命令：\n"
                . "/stats - 查看入库统计\n"
                . "/recent - 最近 5 条记录"
            ),
            str_starts_with($text, '/stats') => $this->handleStats($chatId),
            str_starts_with($text, '/recent') => $this->handleRecent($chatId),
            default => $this->sendMessage($chatId, '请直接发送视频、图片或文件给我。'),
        };
    }

    private function handleStats(int $chatId): void
    {
        $total = MediaResource::count();
        $videos = MediaResource::where('file_type', 'video')->count();
        $images = MediaResource::where('file_type', 'image')->count();
        $docs = MediaResource::where('file_type', 'document')->count();
        $totalSize = MediaResource::sum('file_size');

        $this->sendMessage($chatId,
            "📊 入库统计\n\n"
            . "总计：{$total} 个文件\n"
            . "🎬 视频：{$videos}\n"
            . "🖼 图片：{$images}\n"
            . "📄 文件：{$docs}\n"
            . "💾 总大小：{$this->formatFileSize($totalSize)}"
        );
    }

    private function handleRecent(int $chatId): void
    {
        $items = MediaResource::orderByDesc('created_at')->take(5)->get();

        if ($items->isEmpty()) {
            $this->sendMessage($chatId, '📭 暂无记录。');
            return;
        }

        $lines = $items->map(fn ($item) => sprintf(
            "#%d [%s] %s (%s)",
            $item->id,
            $item->file_type,
            $item->caption ?: $item->file_name,
            $item->created_at->format('m-d H:i'),
        ));

        $this->sendMessage($chatId, "📋 最近入库：\n\n" . $lines->implode("\n"));
    }

    /**
     * 发送消息到指定 Chat
     */
    public function sendMessage(int $chatId, string $text): void
    {
        Http::post("{$this->apiBase}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    /**
     * Long Polling 获取更新
     */
    public function getUpdates(int $offset = 0, int $timeout = 30): array
    {
        $response = Http::timeout($timeout + 5)->get("{$this->apiBase}/getUpdates", [
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => ['message'],
        ]);

        return $response->json('result', []);
    }

    /**
     * 设置 Webhook
     */
    public function setWebhook(string $url): array
    {
        $response = Http::post("{$this->apiBase}/setWebhook", [
            'url' => $url,
            'allowed_updates' => ['message'],
        ]);

        return $response->json();
    }

    public function deleteWebhook(): array
    {
        return Http::post("{$this->apiBase}/deleteWebhook")->json();
    }

    public function getMe(): array
    {
        return Http::get("{$this->apiBase}/getMe")->json();
    }

    private function guessExtension(string $fileType): string
    {
        return match ($fileType) {
            'video' => 'mp4',
            'image' => 'jpg',
            default => 'bin',
        };
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
