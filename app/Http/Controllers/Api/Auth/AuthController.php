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
        $user = $this->auth->register(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->input('device_name', 'api')
        );

        return $this->ok([
            'message' => 'Registered successfully. Please check your email to verify your account.',
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
        $response = $this->ok(['message' => 'Logged out.']);
        // Remove auth_token cookie from browser
        return $response->withCookie(cookie()->forget('auth_token'));
    }

    public function logoutAll(Request $request)
    {
        $request->user()?->tokens()?->delete();
        $response = $this->ok(['message' => 'Logged out from all devices.']);
        return $response->withCookie(cookie()->forget('auth_token'));
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
                // Revoke all existing personal access tokens so previous sessions are logged out
                try {
                    $user->tokens()?->delete();
                } catch (\Throwable $e) {
                    // ignore token deletion errors
                }
            }
        );
        
        if ($status !== Password::PASSWORD_RESET) {
            return $this->fail('Invalid or expired reset token.', 400, 'INVALID_RESET_TOKEN');
        }

        $response = $this->ok([
            'message' => 'Password has been reset successfully.',
        ]);

        // Ensure any auth cookie on the client is removed
        return $response->withCookie(cookie()->forget('auth_token'));
    }
    
}



