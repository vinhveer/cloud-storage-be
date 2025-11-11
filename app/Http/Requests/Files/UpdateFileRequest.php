<?php

namespace App\Http\Requests\Files;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Contracts\Validation\Validator;

class UpdateFileRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:255'],
            // Do not check exists here; Service will validate ownership and existence
            'folder_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($v) {
            $data = $this->all();
            if (! array_key_exists('display_name', $data) && ! array_key_exists('folder_id', $data)) {
                $v->errors()->add('payload', 'At least one of display_name or folder_id must be provided');
            }
        });
    }
}
