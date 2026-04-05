<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\HlsController;
use App\Jobs\TranscodeVideoJob;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoManageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Video::with('category');

        if ($keyword = $request->query('keyword')) {
            $query->where('title', 'like', "%{$keyword}%");
        }
        if ($request->query('is_vip') !== null) {
            $query->where('is_vip', (int) $request->query('is_vip'));
        }
        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        return response()->json(
            $query->orderByDesc('id')->paginate($request->query('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'cover_url' => 'required|string',
            'video_url' => 'required|string',
            'preview_url' => 'nullable|string',
            'is_vip' => 'required|boolean',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer|min:0',
        ]);

        $video = Video::create($data);

        return response()->json($video, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $video = Video::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'cover_url' => 'sometimes|string',
            'video_url' => 'sometimes|string',
            'preview_url' => 'nullable|string',
            'is_vip' => 'sometimes|boolean',
            'category_id' => 'sometimes|exists:categories,id',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer|min:0',
        ]);

        $video->update($data);

        return response()->json($video);
    }

    public function show(int $id): JsonResponse
    {
        $video = Video::with('category')->findOrFail($id);
        $mediaBase = rtrim(config('app.media_base_url'), '/');

        $isHls = $video->hls_path && $video->transcode_status === 'done';

        return response()->json([
            ...$video->toArray(),
            'play_url' => $isHls
                ? "{$mediaBase}/{$video->hls_path}"
                : ($video->video_url ? "{$mediaBase}/{$video->video_url}" : null),
            'play_type' => $isHls ? 'hls' : 'mp4',
            'key_url' => $isHls ? HlsController::signedKeyUrl($video->id) : null,
        ]);
    }

    public function retranscode(int $id): JsonResponse
    {
        $video = Video::findOrFail($id);

        if (! $video->video_url) {
            return response()->json(['message' => '无原始视频文件'], 422);
        }

        $video->update(['transcode_status' => 'pending']);
        TranscodeVideoJob::dispatch($video->id);

        return response()->json(['message' => '转码已重新排队']);
    }

    public function destroy(int $id): JsonResponse
    {
        Video::findOrFail($id)->delete();

        return response()->json(['message' => '已删除']);
    }
}
