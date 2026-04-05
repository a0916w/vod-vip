<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EncryptApiResponse
{
    private const EXCLUDED_PREFIXES = [
        'api/admin',
        'api/telegram',
        'api/payment',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($request->path(), $prefix)) {
                return $response;
            }
        }

        $key = hex2bin(config('app.api_encrypt_key', ''));
        if (! $key || strlen($key) !== 32) {
            return $response;
        }

        $json = $response->getContent();
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($json, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            return $response;
        }

        return response()->json(
            ['_e' => base64_encode($iv . $ciphertext)],
            $response->getStatusCode()
        );
    }
}
