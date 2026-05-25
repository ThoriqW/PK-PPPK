<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Publicly accessible verification page. Reached via QR scan only.
 *
 * Security:
 *  - lookup is by random qr_token, never by sequential id
 *  - exposes ONLY: nama pegawai, nomor perjanjian, status
 *  - no PII (NIP, NIK, DOB, OPD address) is rendered
 *  - rate-limited via the 'verify' RateLimiter (60/min per IP)
 *  - no-store cache header
 */
class VerificationController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $agreement = Agreement::query()
            ->where('qr_token', $token)
            ->whereIn('status', [Agreement::STATUS_AKTIF, Agreement::STATUS_ARSIP, Agreement::STATUS_DIBATALKAN])
            ->with(['employee:id,full_name'])
            ->first();

        return response()
            ->view('verify', [
                'agreement' => $agreement,
                'verifiedAt' => now(),
            ])
            ->header('Cache-Control', 'no-store, max-age=0')
            ->header('X-Frame-Options', 'DENY')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
