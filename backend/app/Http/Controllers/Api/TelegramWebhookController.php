<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramBotService $bot): JsonResponse
    {
        $secretToken = config('telegram.webhook_secret');
        if ($secretToken && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secretToken) {
            return response()->json(['status' => 'unauthorized'], 403);
        }

        $update = $request->all();
        $bot->handleUpdate($update);

        return response()->json(['status' => 'ok']);
    }
}
