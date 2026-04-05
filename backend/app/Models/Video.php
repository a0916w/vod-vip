<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'cover_url',
        'video_url',
        'hls_path',
        'hls_key',
        'transcode_status',
        'preview_url',
        'is_vip',
        'category_id',
        'description',
        'duration',
        'view_count',
    ];

    protected $hidden = ['hls_key'];

    protected function casts(): array
    {
        return [
            'is_vip' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }
}
