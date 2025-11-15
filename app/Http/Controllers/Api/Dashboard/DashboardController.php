<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Dashboard\RecentRequest;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function overview(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $this->dashboard->getOverview($user);
        return $this->ok($data);
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $validated = $request->validate([
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d'],
        ]);

        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        $data = $this->dashboard->getStats($user, $startDate, $endDate);
        return $this->ok($data);
    }
}
