### Mục tiêu

Tài liệu này mô tả kiến trúc và quy ước dùng trong thư mục `app/` để phát triển API Laravel có tổ chức, dễ mở rộng, dễ bảo trì và tránh lỗi giữa các lần phát triển sau.

### Cấu trúc thư mục

```
app/
 ├─ Http/
 │   ├─ Controllers/Api/
 │   │   └─ <Feature>/
 │   ├─ Requests/
 │   │   └─ <Feature>/
 │   └─ Middleware/
 ├─ Services/
 ├─ Repositories/
 ├─ Exceptions/
 └─ Support/
     ├─ Traits/
     └─ Helpers/
```

### Triết lý kiến trúc

- **Controller mảnh**: Chỉ nhận request, gọi Service, trả response chuẩn.
- **Service mạnh**: Chứa nghiệp vụ, gọi Repository, ném exception khi cần.
- **Repository rõ ràng**: Bọc truy cập DB, không chứa nghiệp vụ.
- **Validation tập trung**: Dùng `FormRequest` cho mọi input.
- **Exception tập trung**: Chuẩn JSON lỗi qua cấu hình `bootstrap/app.php`.
- **Response thống nhất**: Dùng `ApiResponse` trait hoặc helper.

### Luồng Request → Response

1) Client → Route → Controller action  
2) Controller nhận DTO từ `FormRequest` (đã validate)  
3) Controller gọi Service thực thi nghiệp vụ  
4) Service tương tác Repository/Model; ném `DomainValidationException`/`ApiException` khi cần  
5) Controller trả JSON chuẩn qua trait `ApiResponse` (`ok/created/noContent/fail`)

### Quy ước Controller

- Đặt dưới `App\Http\Controllers\Api\<Feature>`; kế thừa `BaseApiController`.
- Mỗi feature có folder riêng, đặt kèm tài liệu và ví dụ HTTP:
  - `<feature>.docs.md` — hướng dẫn nhanh, response mẫu, lỗi phổ biến.
  - `<feature>.api.http` — request mẫu để kiểm thử nhanh.
- Controller không chứa nghiệp vụ.
- Trả về bằng các phương thức: `ok($data)`, `created($data)`, `noContent()`.

### Quy ước Service

- Một use case → một Service (hoặc nhóm liên quan).
- Tên động từ rõ nghĩa: `RegisterUserService`, `UploadFileService`.
- Không trực tiếp truy cập request, không trả `Response`.
- Ném `DomainValidationException` khi vi phạm nghiệp vụ.

### Quy ước Repository

- Mỗi aggregate/table chính có một Repository tương ứng.
- Không chứa nghiệp vụ; chỉ truy vấn, lưu, phân trang, lọc.
- Interface tuỳ chọn nếu cần thay thế dễ dàng.

### Validation

- Mọi endpoint phải có `FormRequest` riêng trong `Http/Requests`.
- Override `failedValidation` đã chuẩn hoá JSON 422.

### Exception & Error format

- Dùng `ApiException` cho lỗi có HTTP status tuỳ biến.
- Dùng `DomainValidationException` cho vi phạm nghiệp vụ.
- `bootstrap/app.php` đã cấu hình render về dạng JSON:

```json
{
  "success": false,
  "data": null,
  "error": {
    "message": "...",
    "code": "...",
    "errors": {"field": ["..."]}
  },
  "meta": null
}
```

### Response thống nhất

- Trait: `App\Support\Traits\ApiResponse`  
- Helper: `api_ok($data, $meta)` (autoload trong `composer.json`)

Thành công:

```json
{
  "success": true,
  "data": {"...": "..."},
  "error": null,
  "meta": null
}
```

### Middleware

- `ForceJson`: ép header `Accept: application/json` để trả JSON nhất quán.
- Đăng ký trong `bootstrap/app.php` qua `$middleware->append(...)`.

### Auth

- Dùng Laravel Sanctum (Personal Access Tokens) cho token-based.
- Middleware bảo vệ: `auth:sanctum`.
- Controller: `App\Http\Controllers\Api\Auth\AuthController`.

Endpoints đã triển khai:

- `POST /api/auth/register` — body: `{ "name", "email", "password", "password_confirmation", "device_name?" }`
  - Validate bởi `RegisterRequest`.
  - Trả về: `token` và thông tin `user`.
  - Trường hợp email trùng: ném `ApiException(422, EMAIL_TAKEN)`.
- `POST /api/auth/login` — body: `{ "email", "password", "device_name?" }`
  - Validate bởi `LoginRequest`.
  - Sai thông tin: ném `ApiException(401, INVALID_CREDENTIALS)`.
  - Trả về: `token` và `user`.
- `GET /api/auth/me` — yêu cầu `auth:sanctum`.
  - Trả về thông tin người dùng hiện tại.
- `POST /api/auth/logout` — yêu cầu `auth:sanctum`.
  - Thu hồi token hiện tại.
- `POST /api/auth/logout-all` — yêu cầu `auth:sanctum`.
  - Thu hồi tất cả token của người dùng.

Ví dụ cURL nhanh:

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

# Me (yêu cầu token)
TOKEN="<PAT>"
curl -sS -X GET "$BASE/api/auth/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# Logout current token
curl -sS -X POST "$BASE/api/auth/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# Logout all tokens
curl -sS -X POST "$BASE/api/auth/logout-all" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### Quy ước đặt tên

- Class PascalCase, method camelCase, biến camelCase rõ nghĩa.
- Tên thể hiện mục đích: `findUserByEmail`, `registerUser`.
- Tránh viết tắt mơ hồ.

### Checklist thêm tính năng mới

1) Tạo `FormRequest` cho input.  
2) Tạo `Service` thực thi nghiệp vụ.  
3) (Tuỳ chọn) Tạo `Repository` cho truy cập DB.  
4) Tạo `Controller` dưới `Api/<Feature>` (kế thừa `BaseApiController`).  
5) Thêm route trong `routes/api.php` trỏ về namespace mới.  
6) Viết kèm `<feature>.docs.md` và `<feature>.api.http` ngay trong folder feature.  
7) Trả `ok/created/noContent` hoặc ném exception phù hợp.