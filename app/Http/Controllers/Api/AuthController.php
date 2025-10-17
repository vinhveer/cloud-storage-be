<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

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
}


