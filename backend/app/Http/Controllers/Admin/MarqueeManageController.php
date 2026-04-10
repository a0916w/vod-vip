<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarqueeItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarqueeManageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            MarqueeItem::query()
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'content' => 'required|string|max:255',
            'is_active' => 'required|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $item = MarqueeItem::create([
            'content' => $data['content'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return response()->json($item, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = MarqueeItem::findOrFail($id);

        $data = $request->validate([
            'content' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $item->update($data);

        return response()->json($item);
    }

    public function destroy(int $id): JsonResponse
    {
        MarqueeItem::findOrFail($id)->delete();

        return response()->json(['message' => '已删除']);
    }
}
