<?php

namespace App\Http\Requests\PublicLink;

use App\Http\Requests\BaseFormRequest;

class StorePublicLinkRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'shareable_type' => ['required', 'string', 'in:file,folder'],
            'shareable_id' => ['required', 'integer', 'min:1'],
            'permission' => ['required', 'string', 'in:view,download'],
            'expired_at' => ['nullable', 'date'],
        ];
    }
}
