<?php

namespace App\Console\Commands;

use App\Jobs\TranscodeVideoJob;
use App\Models\Video;
use Illuminate\Console\Command;

class TranscodeAllVideosCommand extends Command
{
    protected $signature = 'videos:transcode {--force : Re-transcode all including done}';

    protected $description = 'Queue HLS transcoding for all videos that are not yet transcoded';

    public function handle(): int
    {
        $query = Video::whereNotNull('video_url')->where('video_url', '!=', '');

        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('transcode_status')
                    ->orWhere('transcode_status', 'failed');
            });
        }

        $videos = $query->get();

        if ($videos->isEmpty()) {
            $this->info('No videos need transcoding.');

            return self::SUCCESS;
        }

        $this->info("Queuing {$videos->count()} video(s) for transcoding...");

        foreach ($videos as $video) {
            $video->update(['transcode_status' => 'pending']);
            TranscodeVideoJob::dispatch($video->id);
            $this->line("  Queued: #{$video->id} - {$video->title}");
        }

        $this->info('Done. Run `php artisan queue:work` to process the jobs.');

        return self::SUCCESS;
    }
}
