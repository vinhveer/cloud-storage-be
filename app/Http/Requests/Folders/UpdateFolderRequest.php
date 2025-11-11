<?php

namespace App\Http\Requests\Folders;

use App\Http\Requests\BaseFormRequest;

class UpdateFolderRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'folder_name' => ['required', 'string', 'max:255'],
        ];
    }
}
