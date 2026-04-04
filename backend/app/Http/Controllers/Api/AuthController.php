<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nickname' => 'required|string|max:50|unique:users,nickname',
        ]);

        $password = bin2hex(random_bytes(8));

        $user = User::create([
            'nickname' => $data['nickname'],
            'password' => $password,
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => '注册成功',
            'user' => $user,
            'token' => $token,
            'plain_password' => $password,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'account' => 'required|string',
            'password' => 'required|string',
        ]);

        $account = $request->account;
        $user = User::where('nickname', $account)->orWhere('email', $account)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'account' => ['账号或密码不正确'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => '登录成功',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => '已退出登录']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'is_vip' => $user->isVip(),
            'is_admin' => $user->is_admin,
            'vip_expired_at' => $user->vip_expired_at?->toDateTimeString(),
        ]);
    }
}
