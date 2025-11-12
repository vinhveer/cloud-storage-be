<?php

namespace App\Http\Requests\Bulk;

use Illuminate\Foundation\Http\FormRequest;

class BulkMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth will be enforced in controller via middleware; allow validation to run
        return true;
    }

    public function rules(): array
    {
        return [
            'file_ids' => ['sometimes', 'array'],
            'file_ids.*' => ['integer'],
            'folder_ids' => ['sometimes', 'array'],
            'folder_ids.*' => ['integer'],
            // allow null or omitted to represent root; validate when provided
            'destination_folder_id' => ['sometimes', 'nullable', 'integer', 'exists:folders,id'],
        ];
    }
}
