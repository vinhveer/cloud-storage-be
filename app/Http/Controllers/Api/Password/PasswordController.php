<?php

namespace App\Http\Controllers\Api\Password;

use App\Http\Controllers\Api\BaseApiController;
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
