<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;

class SearchSuggestionsRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'q' => 'required|string|min:1',
            'type' => 'nullable|string|in:file,folder,all',
            'limit' => 'nullable|integer|min:1|max:100',
        ];
    }
}
