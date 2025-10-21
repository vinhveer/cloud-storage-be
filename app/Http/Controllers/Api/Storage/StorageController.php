<?php

namespace App\Http\Controllers\Api\Storage;

use App\Http\Controllers\Api\BaseApiController;

class StorageController extends BaseApiController
{
    public function usage() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function breakdown() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function limit() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
