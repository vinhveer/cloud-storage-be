<?php

namespace App\Http\Requests\EmailVerification;

use App\Http\Requests\BaseFormRequest;

class ResendRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}


