## API Authentication (Laravel Sanctum)

Tài liệu này tóm tắt 2 chế độ dùng Sanctum và cách kiểm thử nhanh bằng cURL.

### Chế độ hỗ trợ

- Token-based (Mobile/API server-to-server): Dùng Personal Access Token (PAT).
- SPA Cookie-based (Single Page App): Dùng session cookie + CSRF.

### Yêu cầu tối thiểu

- Cài Sanctum (đã có trong `composer.json`).
- Model `User` cần trait `Laravel\Sanctum\HasApiTokens` để tạo/revoke token.
- Nếu dùng SPA cookie: bật middleware `EnsureFrontendRequestsAreStateful` cho domain SPA.

### Headers chung

- JSON: `Accept: application/json`, `Content-Type: application/json`.
- Token-based: `Authorization: Bearer <token>`.

---

## 1) Token-based (khuyến nghị cho mobile/API)

### Đề xuất endpoints

- `POST /api/auth/login` → xác thực credential, trả về PAT.
- `POST /api/auth/logout` → thu hồi token hiện tại.
- `POST /api/auth/logout-all` → thu hồi tất cả token của người dùng.
- `GET /api/auth/me` → trả về thông tin người dùng hiện tại.

Gợi ý triển khai (service/controller):

```php
// Login
// $user->createToken('device-name')->plainTextToken

// Logout current token
// $request->user()->currentAccessToken()->delete()

// Logout all
// $request->user()->tokens()->delete()
```

### cURL kiểm thử (Token-based)

Lưu ý: Các endpoint này cần được triển khai trước khi chạy cURL.

```bash
BASE=http://localhost:8000

# 1) Login lấy token
curl -sS -X POST "$BASE/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "secret"
  }' | jq .

# Giả sử đáp ứng:
# {"success":true,"data":{"token":"<PAT>"},"error":null,"meta":null}

TOKEN="<PAT>"

# 2) Lấy thông tin user hiện tại
curl -sS -X GET "$BASE/api/auth/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | jq .

# 3) Gọi endpoint yêu cầu auth (ví dụ tạo folder)
curl -sS -X POST "$BASE/api/folders" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "folder_name": "Tai lieu",
    "parent_folder_id": null
  }' | jq .

# 4) Logout current token
curl -sS -X POST "$BASE/api/auth/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | jq .

# 5) Logout all tokens
curl -sS -X POST "$BASE/api/auth/logout-all" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | jq .
```

---

## 2) SPA Cookie-based (nếu có frontend cùng domain)

Yêu cầu:

- Cấu hình `SANCTUM_STATEFUL_DOMAINS` và session/cookie domain.
- Dùng `EnsureFrontendRequestsAreStateful` trong pipeline cho SPA.

Luồng chuẩn:

1) GET `/sanctum/csrf-cookie` để nhận CSRF cookie.  
2) POST `/login` với body JSON `email`/`password`.  
3) Gọi API tiếp theo kèm cookie session và header `X-XSRF-TOKEN`.

### cURL kiểm thử (SPA cookie)

```bash
BASE=http://localhost:8000

# 1) Nhận CSRF cookie
curl -c cookies.txt -sS -X GET "$BASE/sanctum/csrf-cookie"

# 2) Login (session cookie sẽ lưu trong cookies.txt)
curl -b cookies.txt -c cookies.txt -sS -X POST "$BASE/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "secret"
  }' | jq .

# 3) Gọi API bảo vệ bằng session cookie
curl -b cookies.txt -sS -X GET "$BASE/api/auth/me" \
  -H "Accept: application/json" | jq .

# 4) Logout session
curl -b cookies.txt -c cookies.txt -sS -X POST "$BASE/logout" \
  -H "Accept: application/json" | jq .
```

---

## Lưu ý & best practices

- Bắt buộc thêm `HasApiTokens` vào `App\\Models\\User` khi dùng token.
- Bảo vệ route bằng middleware `auth:sanctum`.
- Thời hạn/ghi chú token: đặt `name` token theo thiết bị/ngữ cảnh.
- Thu hồi token khi logout hoặc khi nghi ngờ rò rỉ.
- Với SPA: đảm bảo CORS, cookie domain, HTTPS trong môi trường thực.


