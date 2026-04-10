<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public const DEFAULTS = [
        'brand_badge' => 'VOD',
        'site_name' => 'VIP 影院',
        'footer_text' => 'VOD-VIP. All rights reserved.',
        'browser_title' => 'VOD-VIP 影院',
        'home_seo_title' => 'VOD-VIP 影院 - 精选高清视频点播平台',
        'logo_image_url' => '',
        'favicon_url' => '/favicon.ico',
        'vip_trial_seconds' => 30,
        'search_hint_text' => '',
        'search_hint_color' => '#f8fafc',
        'search_hint_font_size' => 14,
        'search_hint_font_weight' => 'normal',
        'search_hint_tail_color' => '#f59e0b',
        'search_hint_tail_font_size' => 14,
        'search_hint_tail_font_weight' => 'bold',
    ];

    public static function publicSettings(): array
    {
        $stored = self::query()
            ->whereIn('key', array_keys(self::DEFAULTS))
            ->pluck('value', 'key')
            ->all();

        return array_merge(self::DEFAULTS, $stored);
    }

    public static function saveSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            self::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
