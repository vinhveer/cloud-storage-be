<?php

use Illuminate\Support\Facades\Route;

// Redirect password reset links to frontend reset page
Route::get('/password/reset/{token}', function ($token) {
    $frontend = rtrim(env('FRONTEND_URL', 'https://cloudfe.nguyenquangvinh.id.vn'), '/');
    $email = request()->query('email');
    $url = $frontend . '/reset-password?token=' . urlencode($token);
    if ($email) {
        $url .= '&email=' . urlencode($email);
    }
    return redirect()->away($url);
})->name('password.reset');

Route::get('/docs', [SwaggerController::class, 'docs'])
    ->name('l5-swagger.default.docs');