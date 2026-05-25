<?php

use App\Http\Controllers\Public\VerificationController;
use Illuminate\Support\Facades\Route;

// Admin SPA shell. The Vue router takes over from here under /admin/*.
Route::view('/', 'app');
Route::view('/admin/{any?}', 'app')->where('any', '.*');

// Public verification - the ONLY publicly reachable application route
// besides login. Throttled per IP.
Route::middleware('throttle:verify')
    ->get('/verify/{token}', [VerificationController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{20,80}')
    ->name('public.verify');
