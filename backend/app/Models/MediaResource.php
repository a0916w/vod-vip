<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaResource extends Model
{
    protected $fillable = [
        'telegram_file_id',
        'file_type',
        'file_name',
        'local_path',
        'file_size',
        'caption',
        'from_user_id',
        'from_username',
        'mime_type',
        'duration',
        'width',
        'height',
        'synced_to_video',
    ];

    protected function casts(): array
    {
        return [
            'synced_to_video' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function getPublicUrlAttribute(): string
    {
        return asset('storage/' . $this->local_path);
    }
}
