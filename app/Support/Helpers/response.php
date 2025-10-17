<?php

if (! function_exists('api_ok')) {
    function api_ok(mixed $data = null, ?array $meta = null) {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => $meta,
        ], 200);
    }
}


