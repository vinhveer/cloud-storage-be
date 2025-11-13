<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends BaseApiController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function register(RegisterRequest $request)
    {
        [$user, $token] = $this->auth->register(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->input('device_name', 'api')
        );

        return $this->ok([
            'message' => 'Registered successfully.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function login(LoginRequest $request)
    {
        [$user, $token] = $this->auth->login(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->input('device_name', 'api')
        );

        return $this->ok([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return $this->ok([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return $this->ok(['message' => 'Logged out.']);
    }

    public function logoutAll(Request $request)
    {
        $request->user()?->tokens()?->delete();
        return $this->ok(['message' => 'Logged out from all devices.']);
    }
    
    public function forgot(ForgotPasswordRequest $request)
    {
        $email = $request->string('email')->toString();
        Password::sendResetLink(['email' => $email]);
        return $this->ok([
            'message' => 'Password reset link sent to your email.',
        ]);
    }
    
    public function reset(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            [
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
                'password_confirmation' => $request->string('password_confirmation')->toString(),
                'token' => $request->string('token')->toString(),
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ]);
                $user->setRememberToken(Str::random(60));
                $user->save();
            }
        );
        
        if ($status !== Password::PASSWORD_RESET) {
            return $this->fail('Invalid or expired reset token.', 400, 'INVALID_RESET_TOKEN');
        }
        
        return $this->ok([
            'message' => 'Password has been reset successfully.',
        ]);
    }
    
}



