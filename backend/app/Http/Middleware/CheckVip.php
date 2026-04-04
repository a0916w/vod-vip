<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckVip
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isVip()) {
            return response()->json([
                'message' => '需要开通 VIP 才能访问此资源',
                'code' => 'vip_required',
            ], 403);
        }

        return $next($request);
    }
}
