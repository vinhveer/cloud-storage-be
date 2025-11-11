<?php

namespace App\Http\Requests\Folders;

use App\Http\Requests\BaseFormRequest;

class ListFoldersRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
