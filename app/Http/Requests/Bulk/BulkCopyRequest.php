<?php

namespace App\Http\Requests\Bulk;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class BulkCopyRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        // Authentication handled by route middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'file_ids' => ['sometimes', 'array'],
            'file_ids.*' => ['integer', 'distinct', 'min:1', 'exists:files,id'],
            'folder_ids' => ['sometimes', 'array'],
            'folder_ids.*' => ['integer', 'distinct', 'min:1', 'exists:folders,id'],
            // allow null to indicate root (fol_folder_id = null)
            'destination_folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $data = $this->validator->getData();
            $hasFiles = array_key_exists('file_ids', $data) && is_array($data['file_ids']) && count($data['file_ids']) > 0;
            $hasFolders = array_key_exists('folder_ids', $data) && is_array($data['folder_ids']) && count($data['folder_ids']) > 0;

            if (! $hasFiles && ! $hasFolders) {
                $validator->errors()->add('payload', 'At least one of file_ids or folder_ids must be provided.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'file_ids.array' => 'file_ids must be an array of integers.',
            'file_ids.*.integer' => 'Each file id must be an integer.',
            'file_ids.*.exists' => 'One or more files not found.',
            'folder_ids.array' => 'folder_ids must be an array of integers.',
            'folder_ids.*.integer' => 'Each folder id must be an integer.',
            'folder_ids.*.exists' => 'One or more folders not found.',
            'destination_folder_id.exists' => 'Destination folder not found.',
        ];
    }
}
