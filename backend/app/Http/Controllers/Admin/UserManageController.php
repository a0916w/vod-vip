<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($keyword = $request->query('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('nickname', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }
        if ($request->query('vip_only')) {
            $query->where('vip_level', '>=', 1)->whereDate('vip_expired_at', '>', now());
        }

        return response()->json(
            $query->orderByDesc('id')->paginate($request->query('per_page', 15))
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'nickname' => 'sometimes|string|max:50',
            'vip_level' => 'sometimes|integer|in:0,1',
            'vip_expired_at' => 'nullable|date',
            'is_admin' => 'sometimes|boolean',
        ]);

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        if ($id == $request->user()->id) {
            return response()->json(['message' => '不能删除自己'], 422);
        }

        User::findOrFail($id)->delete();

        return response()->json(['message' => '已删除']);
    }
}
