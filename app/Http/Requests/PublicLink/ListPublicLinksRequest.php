<?php

namespace App\Http\Requests\PublicLink;

use App\Http\Requests\BaseFormRequest;

class ListPublicLinksRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
