<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\AdminDashboardService;
use Illuminate\Http\Request;

class AdminDashboardController extends BaseApiController
{
    public function __construct(private readonly AdminDashboardService $service) {}

    /**
     * 13.1. API: GET /api/admin/dashboard — Tổng quan
     */
    public function overview()
    {
        $data = $this->service->overview();
        return $this->ok($data);
    }

    /**
     * 13.2. API: GET /api/admin/stats/users — Thống kê người dùng
     */
    public function users()
    {
        $data = $this->service->users();
        return $this->ok($data);
    }

    /**
     * 13.3. API: GET /api/admin/stats/files — Thống kê files
     */
    public function files()
    {
        $data = $this->service->files();
        return $this->ok($data);
    }

    /**
     * 13.4. API: GET /api/admin/stats/storage — Thống kê dung lượng
     */
    public function storage()
    {
        $data = $this->service->storage();
        return $this->ok($data);
    }

    /**
     * 13.5. API: GET /api/admin/stats/activity — Hoạt động hệ thống
     */
    public function activity(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'action' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;
        $action = $validated['action'] ?? null;
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 10);

        $data = $this->service->activity($startDate, $endDate, $action, $page, $perPage);
        return $this->ok($data);
    }
}
