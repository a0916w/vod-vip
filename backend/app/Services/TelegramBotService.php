<?php

namespace App\Services;

use App\Jobs\TranscodeVideoJob;
use App\Models\Category;
use App\Models\MediaResource;
use App\Models\Video;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TelegramBotService
{
    private string $token;
    private string $apiBase;
    private string $fileBase;
    private bool $isLocalServer;
    private int $maxFileSize;
    private array $allowedUsers;

    public function __construct()
    {
        $this->token = config('telegram.bot_token');
        $baseUrl = rtrim(config('telegram.api_base_url', 'https://api.telegram.org'), '/');
        $this->isLocalServer = (bool) config('telegram.use_local_server', false);

        $this->apiBase = "{$baseUrl}/bot{$this->token}";
        $this->fileBase = "{$baseUrl}/file/bot{$this->token}";

        // 标准 API: 20MB, Local Server: 2GB
        $this->maxFileSize = $this->isLocalServer ? 2 * 1024 * 1024 * 1024 : 20 * 1024 * 1024;

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
            $this->sendMessage($message['chat']['id'], '🖼 图片已忽略，仅支持视频下载和转码。');
        } elseif (isset($message['document'])) {
            $doc = $message['document'];
            $mimeType = $doc['mime_type'] ?? '';
            $fileType = str_starts_with($mimeType, 'video/') ? 'video'
                : (str_starts_with($mimeType, 'image/') ? 'image' : 'document');

            if ($fileType !== 'video') {
                $this->sendMessage($message['chat']['id'], '📄 该文件已忽略，仅支持视频下载和转码。');
                return;
            }

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

        if ($fileSize > $this->maxFileSize) {
            $limitStr = $this->isLocalServer ? '2GB' : '20MB';
            $modeStr = $this->isLocalServer ? 'Local Server' : '标准 Bot API';
            $hint = $this->isLocalServer ? '' : "\n💡 搭建 Local Server 可提升到 2GB";
            $this->sendMessage($chatId, "⚠️ 文件超过 {$limitStr}（{$modeStr} 限制）。已记录文件 ID。{$hint}");
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

        $sizeHint = $fileSize > 10 * 1024 * 1024
            ? "⏳ 正在下载（{$this->formatFileSize($fileSize)}），请稍候..."
            : '⏳ 正在下载...';
        $this->sendMessage($chatId, $sizeHint);

        try {
            $response = Http::get("{$this->apiBase}/getFile", ['file_id' => $fileId]);
            $filePath = $response->json('result.file_path');

            if (! $filePath) {
                $this->sendMessage($chatId, '❌ 获取文件路径失败。');
                return;
            }

            $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: $this->guessExtension($fileType);
            $subDir = match ($fileType) {
                'video' => 'telegram/videos',
                'image' => 'telegram/images',
                default => 'telegram/documents',
            };
            $newName = date('Ymd_His') . '_' . Str::random(8) . '.' . $ext;
            $storagePath = "{$subDir}/{$newName}";

            if ($this->isLocalServer && file_exists($filePath)) {
                // Local Server 模式：文件已在本地磁盘，直接复制
                $dest = Storage::disk('public')->path($storagePath);
                $destDir = dirname($dest);
                if (! is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                copy($filePath, $dest);
                $actualSize = filesize($dest);
            } else {
                // 标准 API 或 Local Server HTTP 模式：流式下载
                $downloadUrl = "{$this->fileBase}/{$filePath}";
                $dest = Storage::disk('public')->path($storagePath);
                $destDir = dirname($dest);
                if (! is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                $this->streamDownload($downloadUrl, $dest);
                $actualSize = filesize($dest);
            }

            $resource = MediaResource::create([
                'telegram_file_id' => $fileId,
                'file_type' => $fileType,
                'file_name' => $fileInfo['file_name'] ?? $newName,
                'local_path' => $storagePath,
                'file_size' => $actualSize ?: $fileSize,
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

            $msg = "{$typeEmoji} 入库成功！\n📁 {$resource->file_name}\n💾 {$sizeStr}\n🆔 #{$resource->id}";

            if ($fileType === 'video') {
                $video = $this->autoSyncToVideo($resource);
                if ($video) {
                    $msg .= "\n\n🔄 已自动同步到视频库 (Video #{$video->id})\n⏳ HLS 转码已排队...";
                }
            }

            $this->sendMessage($chatId, $msg);

            Log::info("Telegram Bot: 文件入库成功", [
                'id' => $resource->id,
                'type' => $fileType,
                'size' => $sizeStr,
                'path' => $storagePath,
                'mode' => $this->isLocalServer ? 'local_server' : 'standard_api',
            ]);
        } catch (\Throwable $e) {
            $this->sendMessage($chatId, "❌ 下载失败：{$e->getMessage()}");
            Log::error("Telegram Bot: 下载失败", [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function autoSyncToVideo(MediaResource $resource): ?Video
    {
        try {
            $defaultCategory = Category::where('slug', 'tg')->first();
            $title = $this->buildVideoTitle($resource);

            $video = Video::create([
                'title' => $title,
                'cover_url' => "https://picsum.photos/seed/m{$resource->id}/400/225",
                'video_url' => $resource->local_path,
                'is_vip' => false,
                'category_id' => $defaultCategory?->id ?? 1,
                'description' => $resource->caption,
                'duration' => $resource->duration ?? 0,
                'transcode_status' => 'pending',
            ]);

            $resource->update(['synced_to_video' => true]);

            TranscodeVideoJob::dispatch($video->id);

            return $video;
        } catch (\Throwable $e) {
            Log::error("Telegram Bot: 自动同步视频失败", [
                'resource_id' => $resource->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 流式下载大文件，避免内存溢出
     */
    private function streamDownload(string $url, string $dest): void
    {
        $ch = curl_init($url);
        $fp = fopen($dest, 'wb');

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 3600,
            CURLOPT_CONNECTTIMEOUT => 30,
        ]);

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);
        fclose($fp);

        if (! $success || $httpCode !== 200) {
            @unlink($dest);
            throw new \RuntimeException("下载失败 (HTTP {$httpCode}): {$error}");
        }
    }

    /**
     * 处理文本命令
     */
    private function handleTextCommand(array $message): void
    {
        $text = trim($message['text']);
        $chatId = $message['chat']['id'];

        $modeLabel = $this->isLocalServer ? '🚀 Local Server (最大 2GB)' : '☁️ 标准 API (最大 20MB)';

        match (true) {
            str_starts_with($text, '/start') => $this->sendMessage($chatId,
                "👋 欢迎使用 VOD-VIP 资源采集 Bot！\n\n"
                . "直接向我发送或转发：\n"
                . "🎬 视频\n📄 视频文件\n\n"
                . "我会自动下载、入库并转码。\n"
                . "图片和非视频文件会被忽略。\n\n"
                . "当前模式：{$modeLabel}\n\n"
                . "可用命令：\n"
                . "/stats - 查看入库统计\n"
                . "/recent - 最近 5 条记录\n"
                . "/mode - 查看当前运行模式"
            ),
            str_starts_with($text, '/stats') => $this->handleStats($chatId),
            str_starts_with($text, '/recent') => $this->handleRecent($chatId),
            str_starts_with($text, '/mode') => $this->sendMessage($chatId,
                "⚙️ 运行模式\n\n"
                . "模式：{$modeLabel}\n"
                . "API：{$this->apiBase}\n"
                . "文件上限：{$this->formatFileSize($this->maxFileSize)}"
            ),
            default => $this->sendMessage($chatId, '请直接发送视频或视频文件给我。'),
        };
    }

    private function buildVideoTitle(MediaResource $resource): string
    {
        $text = trim((string) $resource->caption);
        if ($text !== '') {
            $text = preg_replace('/\s+/u', ' ', $text) ?: $text;
            return Str::limit($text, 255, '');
        }

        return (string) ($resource->file_name ?: "video-{$resource->id}");
    }

    private function handleStats(int $chatId): void
    {
        $total = MediaResource::count();
        $videos = MediaResource::where('file_type', 'video')->count();
        $images = MediaResource::where('file_type', 'image')->count();
        $docs = MediaResource::where('file_type', 'document')->count();
        $totalSize = MediaResource::sum('file_size');
        $pending = MediaResource::where('local_path', 'pending_large_file')->count();

        $msg = "📊 入库统计\n\n"
            . "总计：{$total} 个文件\n"
            . "🎬 视频：{$videos}\n"
            . "🖼 图片：{$images}\n"
            . "📄 文件：{$docs}\n"
            . "💾 总大小：{$this->formatFileSize($totalSize)}";

        if ($pending > 0) {
            $msg .= "\n⏳ 待处理大文件：{$pending}";
        }

        $this->sendMessage($chatId, $msg);
    }

    private function handleRecent(int $chatId): void
    {
        $items = MediaResource::orderByDesc('created_at')->take(5)->get();

        if ($items->isEmpty()) {
            $this->sendMessage($chatId, '📭 暂无记录。');
            return;
        }

        $lines = $items->map(fn ($item) => sprintf(
            "#%d [%s] %s (%s) %s",
            $item->id,
            $item->file_type,
            $item->caption ?: $item->file_name,
            $item->created_at->format('m-d H:i'),
            $item->local_path === 'pending_large_file' ? '⏳' : '✅',
        ));

        $this->sendMessage($chatId, "📋 最近入库：\n\n" . $lines->implode("\n"));
    }

    public function sendMessage(int $chatId, string $text): void
    {
        Http::post("{$this->apiBase}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    public function getUpdates(int $offset = 0, int $timeout = 30): array
    {
        $response = Http::timeout($timeout + 5)->get("{$this->apiBase}/getUpdates", [
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => ['message'],
        ]);

        return $response->json('result', []);
    }

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
