<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi salah.'],
            ]);
        }

        $user = $request->user();
        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Akun Anda dinonaktifkan.'],
            ]);
        }

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->audit->log(AuditLog::ACTION_LOGIN, "Login: {$user->email}", $user);

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            $this->audit->log(AuditLog::ACTION_LOGOUT, "Logout: {$user->email}", $user, [], $user->id);
        }

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'user' => $user ? [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ] : null,
        ]);
    }
}
