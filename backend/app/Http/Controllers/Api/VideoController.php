<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoController extends Controller
{
    private function normalizeUrl(?string $url, Request $request): ?string
    {
        if (! $url) return null;

        if (str_starts_with($url, '/storage/')) {
            $url = $request->schemeAndHttpHost() . $url;
        }

        if ($request->secure() && str_starts_with($url, 'http://')) {
            return 'https://' . substr($url, 7);
        }

        return $url;
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

        $videos = $query->orderByDesc('created_at')
            ->paginate($request->query('per_page', 12));

        return response()->json($videos);
    }

    public function latest(): JsonResponse
    {
        return response()->json(
            Video::with('category')->orderByDesc('created_at')->take(8)->get()
        );
    }

    public function recommended(): JsonResponse
    {
        return response()->json(
            Video::with('category')->inRandomOrder()->take(8)->get()
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $video = Video::with('category')->findOrFail($id);

        $video->increment('view_count');

        $user = Auth::guard('sanctum')->user();
        $canPlayFull = ! $video->is_vip || ($user && $user->isVip());

        return response()->json([
            'id' => $video->id,
            'title' => $video->title,
            'cover_url' => $video->cover_url,
            'is_vip' => $video->is_vip,
            'can_play_full' => $canPlayFull,
            'play_url' => $this->normalizeUrl($canPlayFull ? $video->video_url : $video->preview_url, $request),
            'preview_url' => $this->normalizeUrl($video->preview_url, $request),
            'description' => $video->description,
            'duration' => $video->duration,
            'view_count' => $video->view_count,
            'category' => $video->category,
            'vip_required_message' => $canPlayFull ? null : '开通 VIP 观看完整版',
            'is_favorited' => $user ? $user->hasFavorited($video->id) : false,
            'created_at' => $video->created_at,
        ]);
    }
}
