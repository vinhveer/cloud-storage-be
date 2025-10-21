<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class TrashController extends BaseApiController
{
    public function files() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function folders() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function restore(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function destroy(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function emptyTrash() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
