### PublicLink

API tạo và quản lý link public cho file.

#### Endpoints

**POST /api/public-links** - Tạo public link mới
**GET /api/public-links** - Danh sách public links
**GET /api/public-links/{token}** - Xem file qua public link (no auth)
**GET /api/public-links/{token}/preview** - Preview file (no auth)
**GET /api/public-links/{token}/download** - Download file (no auth)
**PUT /api/public-links/{id}** - Cập nhật link
**DELETE /api/public-links/{id}** - Xóa link
**POST /api/public-links/{id}/revoke** - Thu hồi link
**GET /api/files/{id}/public-links** - Danh sách link của file

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "id": 1,
    "token": "abc123",
    "file_id": 1,
    "expires_at": "2025-12-31T23:59:59.000000Z",
    "download_count": 0
  },
  "error": null,
  "meta": null
}
```

