<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nickname',
        'email',
        'phone',
        'password',
        'avatar',
        'vip_level',
        'vip_expired_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'vip_expired_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isVip(): bool
    {
        return $this->vip_level >= 1
            && $this->vip_expired_at
            && $this->vip_expired_at->isFuture();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'favorites')->withPivot('created_at');
    }

    public function hasFavorited(int $videoId): bool
    {
        return $this->favorites()->where('video_id', $videoId)->exists();
    }
}
