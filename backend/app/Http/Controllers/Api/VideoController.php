<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoController extends Controller
{
    private function mediaUrl(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        if (str_starts_with($relativePath, 'http://') || str_starts_with($relativePath, 'https://')) {
            return $relativePath;
        }

        return rtrim(config('app.media_base_url'), '/') . '/' . ltrim($relativePath, '/');
    }

    private function videoListItem(Video $video): array
    {
        return [
            ...$video->toArray(),
            'cover_url' => $this->mediaUrl($video->cover_url) ?? $video->cover_url,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = Video::with('category');

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->query('is_vip') !== null) {
            $query->where('is_vip', (int) $request->query('is_vip'));
        }

        if ($keyword = $request->query('keyword')) {
            $query->where('title', 'like', "%{$keyword}%");
        }

        $paginator = $query->orderByDesc('created_at')
            ->paginate($request->query('per_page', 12));

        $paginator->getCollection()->transform(fn (Video $v) => $this->videoListItem($v));

        return response()->json($paginator);
    }

    public function latest(): JsonResponse
    {
        $videos = Video::with('category')->orderByDesc('created_at')->take(8)->get();

        return response()->json($videos->map(fn (Video $v) => $this->videoListItem($v))->values());
    }

    public function recommended(): JsonResponse
    {
        $videos = Video::with('category')->inRandomOrder()->take(8)->get();

        return response()->json($videos->map(fn (Video $v) => $this->videoListItem($v))->values());
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $video = Video::with('category')->findOrFail($id);
        $settings = SiteSetting::publicSettings();
        $trialSeconds = max(1, (int) ($settings['vip_trial_seconds'] ?? 30));

        $video->increment('view_count');

        $user = Auth::guard('sanctum')->user();
        $canPlayFull = ! $video->is_vip || ($user && $user->isVip());

        $isHls = $video->hls_path && $video->transcode_status === 'done';
        $playType = $isHls ? 'hls' : 'mp4';

        if ($canPlayFull) {
            $playUrl = $isHls
                ? $this->mediaUrl($video->hls_path)
                : $this->mediaUrl($video->video_url);
        } else {
            $playUrl = $this->mediaUrl($video->preview_url) ?: (
                $isHls
                    ? $this->mediaUrl($video->hls_path)
                    : $this->mediaUrl($video->video_url)
            );
        }

        $needTrialKey = $isHls && ! $canPlayFull && ! $video->preview_url;
        $keyUrl = ($isHls && ($canPlayFull || $needTrialKey)) ? HlsController::signedKeyUrl($video->id) : null;

        return response()->json([
            'id' => $video->id,
            'title' => $video->title,
            'cover_url' => $this->mediaUrl($video->cover_url) ?? $video->cover_url,
            'is_vip' => $video->is_vip,
            'can_play_full' => $canPlayFull,
            'play_url' => $playUrl,
            'play_type' => $playType,
            'key_url' => $keyUrl,
            'transcode_status' => $video->transcode_status,
            'preview_url' => $this->mediaUrl($video->preview_url),
            'description' => $video->description,
            'duration' => $video->duration,
            'view_count' => $video->view_count,
            'category' => $video->category,
            'vip_required_message' => $canPlayFull ? null : '开通 VIP 观看完整版',
            'vip_trial_seconds' => $video->is_vip ? $trialSeconds : 0,
            'is_favorited' => $user ? $user->hasFavorited($video->id) : false,
            'created_at' => $video->created_at,
        ]);
    }
}
