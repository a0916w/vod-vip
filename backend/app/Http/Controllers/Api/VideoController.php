<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
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

    public function show(Request $request, int $id): JsonResponse
    {
        $video = Video::with('category')->findOrFail($id);

        $video->increment('view_count');

        $user = $request->user();
        $canPlayFull = ! $video->is_vip || ($user && $user->isVip());

        return response()->json([
            'id' => $video->id,
            'title' => $video->title,
            'cover_url' => $video->cover_url,
            'is_vip' => $video->is_vip,
            'can_play_full' => $canPlayFull,
            'play_url' => $canPlayFull ? $video->video_url : $video->preview_url,
            'preview_url' => $video->preview_url,
            'description' => $video->description,
            'duration' => $video->duration,
            'view_count' => $video->view_count,
            'category' => $video->category,
            'vip_required_message' => $canPlayFull ? null : '开通 VIP 观看完整版',
            'created_at' => $video->created_at,
        ]);
    }
}
