### Dashboard

API tổng quan dashboard.

#### Endpoints

**GET /api/dashboard** - Tổng quan
**GET /api/dashboard/recent** - Hoạt động gần đây
**GET /api/dashboard/stats** - Thống kê

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "total_files": 100,
    "total_folders": 20,
    "storage_used": 1024000,
    "recent_activities": []
  },
  "error": null,
  "meta": null
}
```

