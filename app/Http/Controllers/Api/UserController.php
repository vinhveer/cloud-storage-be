<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class UserController extends BaseApiController
{
    public function show(Request $request)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function updateProfile(Request $request)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function updatePassword(Request $request)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }
}
