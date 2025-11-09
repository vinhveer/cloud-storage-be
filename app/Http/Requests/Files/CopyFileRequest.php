<?php

namespace App\Http\Requests\Files;

use App\Http\Requests\BaseFormRequest;

class CopyFileRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        // Authentication is handled by route middleware; allow here.
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_folder_id' => ['required', 'integer', 'exists:folders,id'],
            // Accept common boolean representations in query or body (true/false, 1/0, on/off)
            'only_latest' => ['sometimes', 'in:1,0,true,false,on,off'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_folder_id.required' => 'Destination folder is required.',
            'destination_folder_id.integer' => 'Destination folder id must be an integer.',
            'destination_folder_id.exists' => 'Destination folder not found.',
            'only_latest.in' => 'The only latest field must be true or false.',
        ];
    }
}
