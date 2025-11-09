<?php

namespace App\Http\Requests\Files;

use App\Http\Requests\BaseFormRequest;

class MoveFileRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        // Authentication handled by route middleware
        return true;
    }

    public function rules(): array
    {
        // Do NOT validate existence here so that non-existent or not-owned
        // folders are handled as domain errors (404) by the service layer.
        return [
            'destination_folder_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_folder_id.required' => 'Destination folder is required.',
            'destination_folder_id.integer' => 'Destination folder id must be an integer.',
            'destination_folder_id.exists' => 'Destination folder not found.',
        ];
    }
}
