<?php

namespace App\Http\Requests\Trash;

use Illuminate\Foundation\Http\FormRequest;

class RestoreTrashRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * For now allow authenticated users; further permission checks live in services.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:file,folder'],
        ];
    }
}
