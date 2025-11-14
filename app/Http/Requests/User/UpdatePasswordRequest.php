<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class UpdatePasswordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }
}


