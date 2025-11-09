<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by auth middleware; allow for now.
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
