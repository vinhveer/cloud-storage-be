<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;

class AdminDashboardController extends BaseApiController
{
    public function overview() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function users() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function files() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function storage() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function activity() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
