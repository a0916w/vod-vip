<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HlsController extends Controller
{
    public function key(Request $request, int $videoId): Response
    {
        $expires = (int) $request->query('expires', 0);
        $signature = (string) $request->query('signature', '');

        if ($expires < time()) {
            abort(403, 'Link expired');
        }

        $expected = self::sign($videoId, $expires);
        if (! hash_equals($expected, $signature)) {
            abort(403, 'Invalid signature');
        }

        $video = Video::findOrFail($videoId);
        if (! $video->hls_key) {
            abort(404);
        }

        $keyBytes = hex2bin($video->hls_key);

        return response($keyBytes, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => strlen($keyBytes),
            'Cache-Control' => 'no-store',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public static function sign(int $videoId, int $expires): string
    {
        return hash_hmac('sha256', "{$videoId}:{$expires}", config('app.hls_key_secret'));
    }

    public static function signedKeyUrl(int $videoId, int $ttlSeconds = 21600): string
    {
        $expires = time() + $ttlSeconds;
        $signature = self::sign($videoId, $expires);

        return url("/api/hls/key/{$videoId}?expires={$expires}&signature={$signature}");
    }
}
