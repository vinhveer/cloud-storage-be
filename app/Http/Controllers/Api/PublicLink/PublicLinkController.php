<?php

namespace App\Http\Controllers\Api\PublicLink;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;

class PublicLinkController extends BaseApiController
{
    public function store(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function index() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function showByToken(string $token) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function destroy(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function update(Request $request, int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function preview(string $token) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function download(string $token) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function revoke(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function forFile(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
