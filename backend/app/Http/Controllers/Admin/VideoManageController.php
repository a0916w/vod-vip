<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function destroy(int $id): JsonResponse
    {
        Video::findOrFail($id)->delete();

        return response()->json(['message' => '已删除']);
    }
}
