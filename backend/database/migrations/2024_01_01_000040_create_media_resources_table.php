<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_resources', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_file_id')->comment('TG 服务器上的唯一文件 ID');
            $table->enum('file_type', ['image', 'video', 'document']);
            $table->string('file_name')->nullable();
            $table->string('local_path')->comment('本地存储路径');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->text('caption')->nullable()->comment('转发时的文字说明');
            $table->unsignedBigInteger('from_user_id')->nullable()->comment('TG 发送者 ID');
            $table->string('from_username', 100)->nullable();
            $table->string('mime_type', 50)->nullable();
            $table->unsignedInteger('duration')->nullable()->comment('视频时长(秒)');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->boolean('synced_to_video')->default(false)->comment('是否已同步到 videos 表');
            $table->timestamps();

            $table->index('telegram_file_id');
            $table->index('file_type');
            $table->index('from_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_resources');
    }
};
