<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class EmailVerificationController extends BaseApiController
{
    public function verify(Request $request, int $id)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function resend(Request $request)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }
}
