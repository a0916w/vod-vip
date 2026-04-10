<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SiteSettingManageController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json($this->settingsPayload());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'brand_badge' => 'required|string|max:20',
            'site_name' => 'required|string|max:50',
            'footer_text' => 'required|string|max:120',
            'browser_title' => 'required|string|max:80',
            'home_seo_title' => 'required|string|max:120',
            'logo_image_url' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|string|max:500',
            'vip_trial_seconds' => 'required|integer|min:1|max:600',
            'search_hint_text' => 'nullable|string|max:120',
            'search_hint_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'search_hint_font_size' => 'required|integer|min:10|max:48',
            'search_hint_font_weight' => 'required|string|in:normal,bold',
            'search_hint_tail_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'search_hint_tail_font_size' => 'required|integer|min:10|max:48',
            'search_hint_tail_font_weight' => 'required|string|in:normal,bold',
            'hls_base_url' => 'nullable|string|max:500',
            'telegram_webhook_url' => 'nullable|string|max:500',
        ]);

        SiteSetting::saveSettings($data);

        return response()->json($this->settingsPayload());
    }

    private function settingsPayload(): array
    {
        return [
            ...SiteSetting::publicSettings(),
            'telegram_bot' => $this->telegramBotInfo(),
        ];
    }

    private function telegramBotInfo(): ?array
    {
        $settings = SiteSetting::publicSettings();
        $token = trim((string) config('telegram.bot_token', ''));
        if ($token === '') {
            return null;
        }

        $botId = explode(':', $token, 2)[0] ?: null;

        try {
            $data = app(TelegramBotService::class)->getMe();
            $result = $data['result'] ?? [];

            return [
                'id' => (string) ($result['id'] ?? $botId),
                'name' => (string) ($result['first_name'] ?? ''),
                'username' => (string) ($result['username'] ?? ''),
                'webhook_url' => (string) ($settings['telegram_webhook_url'] ?? config('telegram.webhook_url', '')),
            ];
        } catch (Throwable) {
            return [
                'id' => (string) $botId,
                'name' => '',
                'username' => '',
                'webhook_url' => (string) ($settings['telegram_webhook_url'] ?? config('telegram.webhook_url', '')),
            ];
        }
    }
}
