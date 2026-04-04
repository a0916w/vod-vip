<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryManageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Category::withCount('videos')->orderBy('sort_order')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'slug' => 'required|string|max:50|unique:categories',
            'sort_order' => 'nullable|integer',
        ]);

        return response()->json(Category::create($data), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:50',
            'slug' => "sometimes|string|max:50|unique:categories,slug,{$id}",
            'sort_order' => 'nullable|integer',
        ]);

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::withCount('videos')->findOrFail($id);

        if ($category->videos_count > 0) {
            return response()->json(['message' => '该分类下还有视频，无法删除'], 422);
        }

        $category->delete();

        return response()->json(['message' => '已删除']);
    }
}
