<?php

namespace App\Http\Requests\Trash;

use App\Http\Requests\BaseFormRequest;

class EmptyTrashRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            // no body expected; keep rules empty
        ];
    }

    public function authorize(): bool
    {
        // user must be authenticated; BaseFormRequest may already enforce this indirectly
        return true;
    }
}
