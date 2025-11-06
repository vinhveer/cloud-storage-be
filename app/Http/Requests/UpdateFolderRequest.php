<?php

namespace App\Http\Requests;

class UpdateFolderRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'folder_name' => ['required', 'string', 'max:255'],
        ];
    }
}
