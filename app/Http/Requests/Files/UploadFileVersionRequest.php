<?php

namespace App\Http\Requests\Files;

use App\Http\Requests\BaseFormRequest;

class UploadFileVersionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'action' => ['required', 'string', 'in:upload,update,restore'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function authorize(): bool
    {
        // Permission checks (owner/edit) are handled in the service layer. Allow request to proceed to service.
        return true;
    }
}
