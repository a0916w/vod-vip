<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\TranscodeVideoJob;
use App\Models\MediaResource;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaResourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MediaResource::query();

        if ($type = $request->query('file_type')) {
            $query->where('file_type', $type);
        }

        if ($request->query('synced') !== null) {
            $query->where('synced_to_video', (bool) $request->query('synced'));
        }

        $resources = $query->orderByDesc('created_at')
            ->paginate($request->query('per_page', 20));

        return response()->json($resources);
    }

    public function show(int $id): JsonResponse
    {
        $resource = MediaResource::findOrFail($id);

        return response()->json([
            ...$resource->toArray(),
            'public_url' => $resource->public_url,
        ]);
    }

    public function syncToVideo(Request $request, int $id): JsonResponse
    {
        $resource = MediaResource::findOrFail($id);

        if ($resource->file_type !== 'video') {
            return response()->json(['message' => '只有视频类型可以同步到视频库'], 422);
        }

        if ($resource->synced_to_video) {
            return response()->json(['message' => '该资源已同步过'], 422);
        }

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'is_vip' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $video = Video::create([
            'title' => $data['title'] ?? $resource->caption ?? $resource->file_name,
            'cover_url' => 'https://picsum.photos/seed/' . $resource->id . '/400/225',
            'video_url' => $resource->local_path,
            'preview_url' => null,
            'is_vip' => $data['is_vip'] ?? 0,
            'category_id' => $data['category_id'],
            'description' => $data['description'] ?? $resource->caption,
            'duration' => $resource->duration ?? 0,
            'transcode_status' => 'pending',
        ]);

        $resource->update(['synced_to_video' => true]);

        TranscodeVideoJob::dispatch($video->id);

        return response()->json([
            'message' => '同步成功，转码已排队',
            'video' => $video,
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $resource = MediaResource::findOrFail($id);

        if ($resource->local_path && $resource->local_path !== 'pending_large_file') {
            Storage::disk('public')->delete($resource->local_path);
        }

        $resource->delete();

        return response()->json(['message' => '已删除']);
    }
}
