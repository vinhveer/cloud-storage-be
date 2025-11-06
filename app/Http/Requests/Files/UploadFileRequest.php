<?php

namespace App\Http\Requests\Files;

use App\Http\Requests\BaseFormRequest;
use App\Models\SystemConfig;

class UploadFileRequest extends BaseFormRequest
{
    public function rules(): array
    {
        // max_upload_size is stored in bytes in system_configs, Laravel's 'max' for files expects kilobytes
        $maxBytes = (int) (SystemConfig::where('config_key', 'max_upload_size')->value('config_value') ?? 0);
        $maxKilobytes = $maxBytes > 0 ? (int) ceil($maxBytes / 1024) : null;

        $fileRules = ['required', 'file'];
        if ($maxKilobytes !== null) {
            $fileRules[] = 'max:' . $maxKilobytes;
        }

        return [
            'file' => $fileRules,
            // Do not use exists here so that non-existent or not-owned folder is handled as domain error (404) in Service
            'folder_id' => ['nullable', 'integer', 'min:1'],
            'display_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
