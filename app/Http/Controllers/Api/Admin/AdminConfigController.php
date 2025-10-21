<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;

class AdminConfigController extends BaseApiController
{
    public function index() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function show(string $key) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function update(Request $request, string $key) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function store(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function destroy(string $key) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
