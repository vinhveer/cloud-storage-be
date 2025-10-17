<?php

namespace App\Http\Controllers\Api;

class HealthController extends BaseApiController
{
    public function __invoke()
    {
        return $this->ok(['status' => 'ok']);
    }
}


