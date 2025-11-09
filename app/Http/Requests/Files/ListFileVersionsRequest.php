<?php

namespace App\Http\Requests\Files;

use App\Http\Requests\BaseFormRequest;

class ListFileVersionsRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
