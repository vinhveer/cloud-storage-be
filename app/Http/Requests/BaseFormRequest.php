<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'message' => 'Validation failed',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors()->toArray(),
            ],
            'meta' => null,
        ], 422);

        throw new HttpResponseException($response);
    }
}


