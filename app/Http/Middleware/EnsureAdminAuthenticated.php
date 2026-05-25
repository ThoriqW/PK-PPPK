<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the request is authenticated AND the user account is_active.
 * The system has only one role (admin BKPSDMD); this middleware just blocks
 * disabled accounts in addition to unauthenticated ones.
 */
class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Sesi telah berakhir, silakan masuk kembali.'],
            ], 401);
        }
        if (! $user->is_active) {
            return response()->json([
                'error' => ['code' => 'USER_DISABLED', 'message' => 'Akun Anda dinonaktifkan.'],
            ], 403);
        }
        return $next($request);
    }
}
