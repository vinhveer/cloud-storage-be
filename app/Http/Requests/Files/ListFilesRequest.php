<?php

namespace App\Http\Requests\Files;

use App\Http\Requests\BaseFormRequest;

class ListFilesRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'folder_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'extension' => ['nullable', 'string', 'max:20'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
