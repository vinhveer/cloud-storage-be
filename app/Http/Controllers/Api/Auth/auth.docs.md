## AuthController — Laravel Sanctum (Token-based)

### Endpoints

- `POST /api/auth/register` — Body: `{ name, email, password, password_confirmation, device_name? }`
- `POST /api/auth/login` — Body: `{ email, password, device_name? }`
- `GET /api/auth/me` — Yêu cầu `auth:sanctum`
- `POST /api/auth/logout` — Yêu cầu `auth:sanctum`
- `POST /api/auth/logout-all` — Yêu cầu `auth:sanctum`

### Ghi chú

- Model `App\\Models\\User` đã dùng trait `Laravel\\Sanctum\\HasApiTokens`.
- Các route yêu cầu đăng nhập được bảo vệ bởi middleware `auth:sanctum`.

### Response mẫu

Đăng ký / đăng nhập thành công:

```json
{
  "success": true,
  "data": {
    "message": "Login successful.",
    "token": "<PAT>",
    "user": {"id": 1, "name": "User", "email": "user@example.com"}
  },
  "error": null,
  "meta": null
}
```

Lỗi xác thực:

```json
{
  "success": false,
  "data": null,
  "error": {"message": "Invalid credentials", "code": "INVALID_CREDENTIALS", "errors": null},
  "meta": null
}
```

### cURL kiểm thử

```bash
BASE=http://localhost:8000

# Register
curl -sS -X POST "$BASE/api/auth/register" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "User",
    "email": "user@example.com",
    "password": "secret",
    "password_confirmation": "secret",
    "device_name": "api"
  }'

# Login
curl -sS -X POST "$BASE/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "secret",
    "device_name": "api"
  }'

# Me
TOKEN="<PAT>"
curl -sS -X GET "$BASE/api/auth/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# Logout current
curl -sS -X POST "$BASE/api/auth/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# Logout all
curl -sS -X POST "$BASE/api/auth/logout-all" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```


