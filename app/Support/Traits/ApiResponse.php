<?php

namespace App\Support\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function respond(mixed $data = null, int $status = 200, ?array $error = null, ?array $meta = null): JsonResponse
    {
        return response()->json([
            'success' => $error === null,
            'data' => $error === null ? $data : null,
            'error' => $error,
            'meta' => $meta,
        ], $status, [], JSON_UNESCAPED_SLASHES);
    }

    protected function ok(mixed $data = null, ?array $meta = null): JsonResponse
    {
        return $this->respond($data, 200, null, $meta);
    }

    protected function created(mixed $data = null, ?array $meta = null): JsonResponse
    {
        return $this->respond($data, 201, null, $meta);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function fail(string $message, int $status = 400, ?string $code = null, ?array $errors = null, ?array $meta = null): JsonResponse
    {
        return $this->respond(null, $status, [
            'message' => $message,
            'code' => $code,
            'errors' => $errors,
        ], $meta);
    }
}


