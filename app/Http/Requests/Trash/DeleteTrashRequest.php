<?php

namespace App\Http\Requests\Trash;

use App\Http\Requests\BaseFormRequest;

class DeleteTrashRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:file,folder'],
        ];
    }
}
