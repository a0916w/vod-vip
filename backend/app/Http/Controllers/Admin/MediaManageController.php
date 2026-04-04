<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaResource;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaManageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MediaResource::query();

        if ($type = $request->query('file_type')) {
            $query->where('file_type', $type);
        }
        if ($request->query('pending')) {
            $query->where('local_path', 'pending_large_file');
        }
        if ($request->query('unsynced')) {
            $query->where('synced_to_video', false);
        }

        return response()->json(
            $query->orderByDesc('id')->paginate($request->query('per_page', 15))
        );
    }

    public function syncToVideo(Request $request, int $id): JsonResponse
    {
        $resource = MediaResource::findOrFail($id);

        if ($resource->synced_to_video) {
            return response()->json(['message' => '已同步过'], 422);
        }
        if ($resource->local_path === 'pending_large_file') {
            return response()->json(['message' => '文件尚未下载，无法同步'], 422);
        }

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'is_vip' => 'nullable|boolean',
        ]);

        $video = Video::create([
            'title' => $data['title'] ?? $resource->caption ?? $resource->file_name,
            'cover_url' => "https://picsum.photos/seed/m{$resource->id}/400/225",
            'video_url' => asset('storage/' . $resource->local_path),
            'is_vip' => $data['is_vip'] ?? 0,
            'category_id' => $data['category_id'],
            'description' => $resource->caption,
            'duration' => $resource->duration ?? 0,
        ]);

        $resource->update(['synced_to_video' => true]);

        return response()->json(['message' => '同步成功', 'video' => $video], 201);
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
