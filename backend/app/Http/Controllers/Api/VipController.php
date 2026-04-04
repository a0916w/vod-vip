<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VipController extends Controller
{
    private const PLANS = [
        'monthly' => ['name' => '月度会员', 'months' => 1, 'price' => 29.90],
        'quarterly' => ['name' => '季度会员', 'months' => 3, 'price' => 68.00],
        'yearly' => ['name' => '年度会员', 'months' => 12, 'price' => 198.00],
    ];

    public function plans(): JsonResponse
    {
        return response()->json(self::PLANS);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|string|in:monthly,quarterly,yearly',
            'payment_method' => 'required|string|in:wechat,alipay',
        ]);

        $plan = self::PLANS[$request->plan];

        $order = Order::create([
            'order_no' => 'VIP' . date('YmdHis') . Str::upper(Str::random(6)),
            'user_id' => $request->user()->id,
            'plan_name' => $plan['name'],
            'months' => $plan['months'],
            'amount' => $plan['price'],
            'status' => 0,
            'payment_method' => $request->payment_method,
        ]);

        // 实际项目中这里调用微信/支付宝 SDK 生成支付参数
        return response()->json([
            'order' => $order,
            'payment_params' => [
                'order_no' => $order->order_no,
                'amount' => $order->amount,
                'method' => $order->payment_method,
                'qr_url' => "https://pay.example.com/qr/{$order->order_no}",
            ],
        ], 201);
    }

    /**
     * 支付回调 (Webhook) — 实际项目中需验签
     */
    public function paymentCallback(Request $request): JsonResponse
    {
        $request->validate([
            'order_no' => 'required|string',
            'transaction_id' => 'required|string',
        ]);

        $order = Order::where('order_no', $request->order_no)
            ->where('status', 0)
            ->firstOrFail();

        $order->update([
            'status' => 1,
            'transaction_id' => $request->transaction_id,
            'paid_at' => now(),
        ]);

        $user = $order->user;
        $baseTime = ($user->isVip() && $user->vip_expired_at->isFuture())
            ? $user->vip_expired_at
            : Carbon::now();

        $user->update([
            'vip_level' => 1,
            'vip_expired_at' => $baseTime->addMonths($order->months),
        ]);

        return response()->json(['message' => '支付成功，VIP 已激活']);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($orders);
    }
}
