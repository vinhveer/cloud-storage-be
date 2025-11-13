<?php

namespace App\Http\Requests\Trash;

use Illuminate\Foundation\Http\FormRequest;

class CombinedTrashIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * We keep authorization in the controller (auth:sanctum middleware),
     * but allow the request through here to validate input.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for combined trash index.
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
