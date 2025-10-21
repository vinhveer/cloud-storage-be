<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class PasswordController extends BaseApiController
{
    public function forgot(Request $request)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function reset(Request $request)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }
}
