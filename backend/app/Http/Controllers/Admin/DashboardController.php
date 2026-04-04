<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaResource;
use App\Models\Order;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'stats' => [
                'total_users' => User::count(),
                'vip_users' => User::where('vip_level', '>=', 1)->whereDate('vip_expired_at', '>', now())->count(),
                'total_videos' => Video::count(),
                'vip_videos' => Video::where('is_vip', 1)->count(),
                'total_orders' => Order::count(),
                'paid_orders' => Order::where('status', 1)->count(),
                'revenue' => (float) Order::where('status', 1)->sum('amount'),
                'total_media' => MediaResource::count(),
                'pending_media' => MediaResource::where('local_path', 'pending_large_file')->count(),
            ],
            'recent_users' => User::orderByDesc('created_at')->take(5)->get(['id', 'nickname', 'email', 'vip_level', 'is_admin', 'created_at']),
            'recent_orders' => Order::with('user:id,nickname')->orderByDesc('created_at')->take(5)->get(),
        ]);
    }
}
