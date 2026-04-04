<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * 收藏/取消收藏（Toggle）
     */
    public function toggle(Request $request, int $videoId): JsonResponse
    {
        $user = $request->user();

        if (! $user->isVip()) {
            return response()->json(['message' => '收藏功能仅限 VIP 会员使用'], 403);
        }

        if ($user->hasFavorited($videoId)) {
            $user->favorites()->detach($videoId);
            return response()->json([
                'is_favorited' => false,
                'message' => '已取消收藏',
            ]);
        }

        $user->favorites()->attach($videoId);

        return response()->json([
            'is_favorited' => true,
            'message' => '收藏成功',
        ]);
    }

    /**
     * 我的收藏列表
     */
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()
            ->favorites()
            ->with('category')
            ->orderByPivot('created_at', 'desc')
            ->paginate($request->query('per_page', 12));

        return response()->json($favorites);
    }

    /**
     * 检查某个视频是否已收藏
     */
    public function check(Request $request, int $videoId): JsonResponse
    {
        return response()->json([
            'is_favorited' => $request->user()->hasFavorited($videoId),
        ]);
    }

    /**
     * 批量检查收藏状态
     */
    public function batchCheck(Request $request): JsonResponse
    {
        $request->validate([
            'video_ids' => 'required|array',
            'video_ids.*' => 'integer',
        ]);

        $favoritedIds = $request->user()
            ->favorites()
            ->whereIn('video_id', $request->video_ids)
            ->pluck('video_id');

        return response()->json([
            'favorited_ids' => $favoritedIds,
        ]);
    }
}
