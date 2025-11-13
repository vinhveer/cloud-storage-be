<?php

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;

class PreviewFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * For preview we allow the service to enforce access rules.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * No validation rules required for this GET endpoint (id is from route, token is optional).
     */
    public function rules(): array
    {
        return [];
    }
}
