<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class FileBulkController extends BaseApiController
{
    public function bulkDelete(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function bulkMove(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function bulkCopy(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function bulkShare(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function bulkDownload(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
