<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(string $name, string $email, string $password, ?string $deviceName = null): array
    {
        if (User::where('email', $email)->exists()) {
            throw new ApiException('Email already taken', 422, 'EMAIL_TAKEN');
        }

        $defaultStorageLimit = SystemConfig::query()
            ->where('config_key', 'default_storage_limit')
            ->value('config_value');

        if ($defaultStorageLimit === null) {
            throw new ApiException('Default storage limit not configured', 500, 'CONFIG_NOT_FOUND');
        }

        $storageLimit = (int) $defaultStorageLimit;

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'storage_limit' => $storageLimit,
            'storage_used' => 0,
        ]);

        $token = $user->createToken($deviceName ?: 'api')->plainTextToken;

        return [$user, $token];
    }

    public function login(string $email, string $password, ?string $deviceName = null): array
    {
        $user = User::where('email', $email)->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            throw new ApiException('Invalid credentials', 401, 'INVALID_CREDENTIALS');
        }

        $token = $user->createToken($deviceName ?: 'api')->plainTextToken;

        return [$user, $token];
    }
}


