<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'cover_url',
        'video_url',
        'preview_url',
        'is_vip',
        'category_id',
        'description',
        'duration',
        'view_count',
    ];

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
}
