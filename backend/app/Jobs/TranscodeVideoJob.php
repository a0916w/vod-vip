<?php

namespace App\Jobs;

use App\Models\MediaResource;
use App\Models\Video;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class TranscodeVideoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public int $videoId) {}

    public function handle(): void
    {
        $video = Video::find($this->videoId);
        if (! $video || ! $video->video_url) {
            return;
        }

        $video->update(['transcode_status' => 'processing']);

        $disk = Storage::disk('public');
        $isRemote = str_starts_with($video->video_url, 'http://') || str_starts_with($video->video_url, 'https://');
        $tempDownload = null;

        if ($isRemote) {
            $localDir = "downloads/{$video->id}";
            $disk->makeDirectory($localDir);
            $ext = pathinfo(parse_url($video->video_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'mp4';
            $localRelative = "{$localDir}/original.{$ext}";
            $localAbsolute = $disk->path($localRelative);

            Log::info("TranscodeVideoJob: downloading remote video {$video->id}: {$video->video_url}");

            try {
                $stream = fopen($video->video_url, 'r');
                if (! $stream) {
                    throw new \RuntimeException('Failed to open remote URL');
                }
                file_put_contents($localAbsolute, $stream);
                fclose($stream);
            } catch (\Throwable $e) {
                $video->update(['transcode_status' => 'failed']);
                Log::error("TranscodeVideoJob: download failed for video {$video->id}: {$e->getMessage()}");

                return;
            }

            $video->update(['video_url' => $localRelative]);
            $inputPath = $localAbsolute;
            $tempDownload = null; // keep the downloaded file
        } else {
            $inputPath = $disk->path($video->video_url);
        }

        if (! file_exists($inputPath)) {
            $video->update(['transcode_status' => 'failed']);
            Log::error("TranscodeVideoJob: input file not found: {$inputPath}");

            return;
        }

        $hlsDir = "hls/{$video->id}";
        $disk->makeDirectory($hlsDir);
        $outputDir = $disk->path($hlsDir);

        $key = random_bytes(16);
        $iv = random_bytes(16);
        $keyHex = bin2hex($key);

        $keyFile = "{$outputDir}/enc.key";
        file_put_contents($keyFile, $key);

        $keyInfoFile = "{$outputDir}/key_info.txt";
        $keyUri = "http://placeholder/key";
        file_put_contents($keyInfoFile, "{$keyUri}\n{$keyFile}\n" . bin2hex($iv));

        $m3u8 = "{$outputDir}/index.m3u8";
        $segmentPattern = "{$outputDir}/seg_%04d.ts";

        $cmd = [
            'ffmpeg', '-y', '-i', $inputPath,
            '-c:v', 'libx264', '-preset', 'fast', '-crf', '23',
            '-force_key_frames', 'expr:gte(t,n_forced*1)',
            '-sc_threshold', '0',
            '-c:a', 'aac', '-b:a', '128k',
            '-hls_time', '1',
            '-hls_list_size', '0',
            '-hls_key_info_file', $keyInfoFile,
            '-hls_segment_filename', $segmentPattern,
            '-f', 'hls',
            $m3u8,
        ];

        Log::info("TranscodeVideoJob: starting ffmpeg for video {$video->id}");

        $result = Process::timeout(3600)->run($cmd);

        @unlink($keyFile);
        @unlink($keyInfoFile);

        if (! $result->successful()) {
            $video->update(['transcode_status' => 'failed']);
            Log::error("TranscodeVideoJob: ffmpeg failed for video {$video->id}", [
                'exit' => $result->exitCode(),
                'stderr' => mb_substr($result->errorOutput(), -2000),
            ]);
            $this->notifyBot($video, 'failed');

            return;
        }

        $video->update([
            'hls_path' => "{$hlsDir}/index.m3u8",
            'hls_key' => $keyHex,
            'transcode_status' => 'done',
        ]);

        Log::info("TranscodeVideoJob: completed for video {$video->id}");
        $this->notifyBot($video, 'done');
    }

    private function notifyBot(Video $video, string $status): void
    {
        try {
            $resource = MediaResource::where('local_path', $video->video_url)
                ->where('synced_to_video', true)
                ->first();

            $chatId = $resource?->from_user_id;
            if (! $chatId) {
                return;
            }

            $emoji = $status === 'done' ? '✅' : '❌';
            $label = $status === 'done' ? 'HLS 转码完成' : 'HLS 转码失败';
            $msg = "{$emoji} {$label}\n📹 {$video->title}\n🆔 Video #{$video->id}";

            app(TelegramBotService::class)->sendMessage($chatId, $msg);
        } catch (\Throwable $e) {
            Log::warning("TranscodeVideoJob: bot notify failed: {$e->getMessage()}");
        }
    }
}
