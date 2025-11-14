<?php

namespace App\Http\Controllers\Api\Password;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Support\Facades\Password;

class PasswordController extends BaseApiController
{
    public function forgot(ForgotPasswordRequest $request)
    {
        $email = $request->string('email')->toString();

        // Always return generic success to prevent user enumeration
        Password::sendResetLink(['email' => $email]);

        return $this->ok([
            'message' => 'Password reset link sent to your email.',
        ]);
    }

    public function reset(\Illuminate\Http\Request $request)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }
}
