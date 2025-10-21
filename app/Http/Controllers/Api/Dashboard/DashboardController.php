<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Api\BaseApiController;

class DashboardController extends BaseApiController
{
    public function overview() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function recent() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function stats() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
