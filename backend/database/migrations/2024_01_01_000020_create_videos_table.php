<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('cover_url');
            $table->string('video_url');
            $table->string('preview_url')->nullable();
            $table->tinyInteger('is_vip')->default(0)->comment('0:免费 1:VIP专属');
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->unsignedInteger('duration')->default(0)->comment('时长(秒)');
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            $table->index(['is_vip', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
