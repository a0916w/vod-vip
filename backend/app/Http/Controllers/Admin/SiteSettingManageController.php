<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSettingManageController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(SiteSetting::publicSettings());
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
        ]);

        SiteSetting::saveSettings($data);

        return response()->json(SiteSetting::publicSettings());
    }
}
