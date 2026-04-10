<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarqueeItem;
use Illuminate\Http\JsonResponse;

class MarqueeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            MarqueeItem::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get(['id', 'content', 'sort_order'])
        );
    }
}
