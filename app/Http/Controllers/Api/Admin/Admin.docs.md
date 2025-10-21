### Admin

API quản trị hệ thống (yêu cầu quyền admin).

#### User Management

**GET /api/admin/users** - Danh sách user
**GET /api/admin/users/{id}** - Chi tiết user
**POST /api/admin/users** - Tạo user mới
**PUT /api/admin/users/{id}** - Cập nhật user
**DELETE /api/admin/users/{id}** - Xóa user
**PUT /api/admin/users/{id}/storage-limit** - Cập nhật storage limit
**GET /api/admin/users/{id}/storage-usage** - Xem storage usage
**PUT /api/admin/users/{id}/role** - Cập nhật role

#### Storage Management

**GET /api/admin/storage/overview** - Tổng quan storage
**GET /api/admin/storage/users** - Storage theo user

#### Config Management

**GET /api/admin/configs** - Danh sách config
**GET /api/admin/configs/{key}** - Chi tiết config
**PUT /api/admin/configs/{key}** - Cập nhật config
**POST /api/admin/configs** - Tạo config mới
**DELETE /api/admin/configs/{key}** - Xóa config

#### Dashboard

**GET /api/admin/dashboard** - Tổng quan admin
**GET /api/admin/stats/users** - Thống kê user
**GET /api/admin/stats/files** - Thống kê file
**GET /api/admin/stats/storage** - Thống kê storage
**GET /api/admin/stats/activity** - Thống kê hoạt động

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "users": [],
    "total": 0
  },
  "error": null,
  "meta": null
}
```

