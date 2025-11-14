<?php

namespace App\Http\Controllers\Api\EmailVerification;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\EmailVerification\ResendRequest;
use App\Models\User;
use App\Notifications\VerifyEmailApiLink;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends BaseApiController
{
    public function verify(Request $request, int $id)
    {
        $user = User::query()->find($id);
        if (! $user) {
            return $this->fail('User not found.', 404, 'USER_NOT_FOUND');
        }

        if (! URL::hasValidSignature($request)) {
            return $this->fail('Invalid or expired verification link.', 400, 'INVALID_VERIFICATION_LINK');
        }

        if ($user->email_verified_at) {
            return $this->ok([
                'message' => 'Email verified successfully.',
            ]);
        }

        $user->email_verified_at = now();
        $user->save();

        event(new Verified($user));

        return $this->ok([
            'message' => 'Email verified successfully.',
        ]);
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
