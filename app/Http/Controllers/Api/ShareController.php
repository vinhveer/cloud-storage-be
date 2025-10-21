<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class ShareController extends BaseApiController
{
    public function store(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function index() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function show(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function update(Request $request, int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function destroy(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function received() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function addUsers(Request $request, int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function removeUser(int $id, int $userId) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function updateUserPermission(Request $request, int $id, int $userId) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
