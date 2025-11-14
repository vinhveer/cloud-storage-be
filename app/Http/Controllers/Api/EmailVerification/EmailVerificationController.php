<?php

namespace App\Http\Controllers\Api\EmailVerification;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\EmailVerification\ResendRequest;
use App\Models\User;
use App\Notifications\VerifyEmailApiLink;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cookie;

class EmailVerificationController extends BaseApiController
{
    public function verify(Request $request, int $id)
    {
        $user = User::query()->find($id);
        $frontend = env('FRONTEND_URL', config('app.url'));
        $redirectToFrontend = function (array $params = []) use ($frontend) {
            $url = rtrim($frontend, '/') . '/email-verified';
            if (! empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return redirect()->away($url);
        };
        if (! $user) {
            if ($request->isMethod('get')) {
                return $redirectToFrontend(['status' => 'error', 'reason' => 'user_not_found']);
            }
            return $this->fail('User not found.', 404, 'USER_NOT_FOUND');
        }

        if (! URL::hasValidSignature($request)) {
            if ($request->isMethod('get')) {
                return $redirectToFrontend(['status' => 'error', 'reason' => 'invalid_or_expired_link']);
            }
            return $this->fail('Invalid or expired verification link.', 400, 'INVALID_VERIFICATION_LINK');
        }

        if ($user->email_verified_at) {
            if ($request->isMethod('get')) {
                return $redirectToFrontend(['status' => 'success']);
            }

            return $this->ok([
                'message' => 'Email verified successfully.',
            ]);
        }

        $user->email_verified_at = now();
        $user->save();

        event(new Verified($user));

        // Create a personal access token and set it as an httpOnly cookie so frontend is auto-logged-in
        try {
            $token = $user->createToken('web')->plainTextToken;
        } catch (\Throwable $e) {
            $token = null;
        }

        $cookie = null;
        if ($token) {
            // Cookie expiration in minutes (e.g., 30 days)
            $minutes = 60 * 24 * 30;
            // For local development we avoid Secure=true to allow http; in production set Secure=true
            $secure = config('app.env') !== 'local';
            // Use domain null so cookie is set on the response host (localhost)
            $cookie = cookie('auth_token', $token, $minutes, '/', null, $secure, true, false, 'Lax');
        }

        if ($request->isMethod('get')) {
            $response = $redirectToFrontend(['status' => 'success']);
            if ($cookie) {
                return $response->withCookie($cookie);
            }
            return $response;
        }

        $response = $this->ok([
            'message' => 'Email verified successfully.',
        ]);

        if ($cookie) {
            return $response->withCookie($cookie);
        }

        return $response;
    }

    public function resend(ResendRequest $request)
    {
        $email = $request->string('email')->toString();
        $user = User::query()->where('email', $email)->first();

        if ($user && ! $user->email_verified_at) {
            $verificationUrl = URL::temporarySignedRoute(
                'api.email.verify',
                now()->addMinutes(60),
                ['id' => $user->id]
            );

            $user->notify(new VerifyEmailApiLink($verificationUrl));
        }

        return $this->ok([
            'message' => 'Verification link resent successfully.',
        ]);
    }
}
