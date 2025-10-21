<?php

namespace App\Http\Controllers\Api\Search;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;

class SearchController extends BaseApiController
{
    public function search(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function files(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function folders(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function suggestions(Request $request) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
