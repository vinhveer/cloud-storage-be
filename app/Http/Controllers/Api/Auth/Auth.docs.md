### Auth

API xác thực người dùng bằng Laravel Sanctum Personal Access Token.

#### Endpoints

**POST /api/auth/register** - Đăng ký user mới
- Body: `name`, `email`, `password`, `password_confirmation`, `device_name`
- Response: `token`, `user`

**POST /api/auth/login** - Đăng nhập
- Body: `email`, `password`, `device_name`
- Response: `token`, `user`

**GET /api/auth/me** - Lấy thông tin user hiện tại
- Auth: Required
- Response: `user`

**POST /api/auth/logout** - Đăng xuất token hiện tại
- Auth: Required
- Response: `message`

**POST /api/auth/logout-all** - Đăng xuất tất cả token
- Auth: Required
- Response: `message`

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "token": "1|abc...",
    "user": {
      "id": 1,
      "name": "User",
      "email": "user@example.com"
    }
  },
  "error": null,
  "meta": null
}
```

#### Lỗi phổ biến

- `EMAIL_TAKEN` (422): Email đã tồn tại
- `INVALID_CREDENTIALS` (401): Sai email hoặc password

