<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderManageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with('user:id,nickname,email');

        if ($status = $request->query('status')) {
            $query->where('status', (int) $status);
        }
        if ($keyword = $request->query('keyword')) {
            $query->where('order_no', 'like', "%{$keyword}%");
        }

        return response()->json(
            $query->orderByDesc('id')->paginate($request->query('per_page', 15))
        );
    }
}
