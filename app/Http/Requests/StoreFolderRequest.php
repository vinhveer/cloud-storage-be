<?php

namespace App\Http\Requests;

class StoreFolderRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'folder_name' => ['required', 'string', 'max:255'],
            'parent_folder_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}


