<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Redirect password reset links to frontend reset page
Route::get('/password/reset/{token}', function ($token) {
    $frontend = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');
    $email = request()->query('email');
    $url = $frontend . '/reset-password?token=' . urlencode($token);
    if ($email) {
        $url .= '&email=' . urlencode($email);
    }
    return redirect()->away($url);
})->name('password.reset');
