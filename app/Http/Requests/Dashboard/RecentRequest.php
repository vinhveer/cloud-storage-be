<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\BaseFormRequest;

class RecentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
