<?php

namespace App\Http\Controllers\Api\Health;

use App\Http\Controllers\Api\BaseApiController;

class HealthController extends BaseApiController
{
    public function __invoke()
    {
        return $this->ok(['status' => 'ok']);
    }
}



