<?php

namespace App\Http\Requests\PublicLink;

use App\Http\Requests\BaseFormRequest;

class UpdatePublicLinkRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'permission' => ['sometimes', 'string', 'in:view,download'],
            'expired_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
