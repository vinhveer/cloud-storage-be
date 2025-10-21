### User

API quản lý thông tin user profile.

#### Endpoints

**GET /api/user** - Lấy thông tin user hiện tại
**PUT /api/user/profile** - Cập nhật profile
**PUT /api/user/password** - Đổi mật khẩu

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com",
    "created_at": "2025-01-01T00:00:00.000000Z"
  },
  "error": null,
  "meta": null
}
```

