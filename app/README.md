### Mục tiêu

Tài liệu này mô tả kiến trúc và quy ước dùng trong thư mục `app/` để phát triển API Laravel có tổ chức, dễ mở rộng, dễ bảo trì và tránh lỗi giữa các lần phát triển sau.

### Cấu trúc thư mục

```
app/
 ├─ Http/
 │   ├─ Controllers/Api/   ← Controller mảnh, trả JSON
 │   ├─ Requests/          ← FormRequest validation
 │   └─ Middleware/        ← ForceJson, Auth...
 ├─ Services/              ← Xử lý nghiệp vụ (business logic)
 ├─ Repositories/          ← Truy cập dữ liệu (Eloquent/Query Builder)
 ├─ Exceptions/            ← Ngoại lệ miền, API exception
 └─ Support/
     ├─ Traits/ApiResponse.php  ← Chuẩn hoá response
     └─ Helpers/response.php    ← Helper nhanh cho response
```

### Triết lý kiến trúc

- **Controller mảnh**: Chỉ nhận request, gọi Service, trả response chuẩn.
- **Service mạnh**: Chứa nghiệp vụ, gọi Repository, ném exception khi cần.
- **Repository rõ ràng**: Bọc truy cập DB, không chứa nghiệp vụ.
- **Validation tập trung**: Dùng `FormRequest` cho mọi input.
- **Exception tập trung**: Chuẩn JSON lỗi qua cấu hình `bootstrap/app.php`.
- **Response thống nhất**: Dùng `ApiResponse` trait hoặc helper.

### Luồng Request → Response

1) Client → Controller (FormRequest validate)  
2) Controller → Service (xử lý nghiệp vụ)  
3) Service → Repository (truy vấn DB)  
4) Service trả dữ liệu hoặc ném `DomainValidationException`/`ApiException`  
5) Controller trả JSON qua `ok()/created()/fail()`

### Quy ước Controller

- Đặt trong `App\Http\Controllers\Api` và kế thừa `BaseApiController`.
- Không viết nghiệp vụ trong Controller.
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

- Khuyến nghị Sanctum/JWT. Áp dụng bằng middleware, không xử lý auth trong Controller.

### Quy ước đặt tên

- Class PascalCase, method camelCase, biến camelCase rõ nghĩa.
- Tên thể hiện mục đích: `findUserByEmail`, `registerUser`.
- Tránh viết tắt mơ hồ.

### Checklist thêm tính năng mới

1) Tạo `FormRequest` validate input.  
2) Tạo `Service` xử lý nghiệp vụ chính.  
3) Tạo `Repository` nếu cần truy cập DB mới.  
4) Tạo Controller action (kế thừa `BaseApiController`) gọi Service.  
5) Trả `ok/created/noContent` hoặc ném exception phù hợp.  
6) Thêm route trong `routes/api.php`.  
7) Viết test.