<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class FileController extends BaseApiController
{
    public function store(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function index(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function show(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function download(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function update(Request $request, int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function destroy(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function restore(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function forceDelete(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function copy(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function move(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function recent() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function sharedWithMe() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function sharedByMe() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
