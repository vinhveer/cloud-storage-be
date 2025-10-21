<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class FileVersionController extends BaseApiController
{
    public function store(Request $request, int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function index(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function show(int $id, int $versionId) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function download(int $id, int $versionId) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function restore(int $id, int $versionId) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function destroy(int $id, int $versionId) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
