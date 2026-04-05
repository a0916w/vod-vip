<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('hls_path')->nullable()->after('video_url');
            $table->string('hls_key', 32)->nullable()->after('hls_path');
            $table->string('transcode_status', 20)->nullable()->after('hls_key');
        });

        DB::table('videos')
            ->where('video_url', 'like', '/storage/%')
            ->update(['video_url' => DB::raw("REPLACE(video_url, '/storage/', '')")]);
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['hls_path', 'hls_key', 'transcode_status']);
        });
    }
};
