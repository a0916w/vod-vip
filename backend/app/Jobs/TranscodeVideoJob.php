<?php

namespace App\Jobs;

use App\Models\Video;
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
        $inputPath = $disk->path($video->video_url);

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
            '-c:a', 'aac', '-b:a', '128k',
            '-hls_time', '10',
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
            return;
        }

        $video->update([
            'hls_path' => "{$hlsDir}/index.m3u8",
            'hls_key' => $keyHex,
            'transcode_status' => 'done',
        ]);

        Log::info("TranscodeVideoJob: completed for video {$video->id}");
    }
}
