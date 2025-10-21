<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;

class AdminUserController extends BaseApiController
{
    public function index(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function show(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function store(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function update(Request $request, int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function destroy(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function updateStorageLimit(Request $request, int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function storageUsage(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function updateRole(Request $request, int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
