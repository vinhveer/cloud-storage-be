# Cloud Storage API – ALL IN ONE

Tài liệu duy nhất này tổng hợp nội dung quan trọng từ toàn bộ các tài liệu trước đây, loại bỏ phần trùng lặp/không thiết yếu. Dành cho developer mới và cũ để nắm hệ thống nhanh, triển khai nhất quán.

## Mục lục

1. Giới thiệu ngắn gọn
2. Kiến trúc & Quy ước (Architecture & Conventions)
3. Chuẩn Response & Exception
4. Xác thực (Laravel Sanctum)
5. Danh sách Endpoints (tóm tắt)
6. Checklist triển khai mỗi endpoint
7. Mô hình dữ liệu (Models) – Tổng quan
8. Kiểm thử nhanh (ví dụ tạo Folder)

---

## Root README (d:\Study\MNM\cloud-storage-be\README.md)

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

-   Simple, fast routing engine.
-   Powerful dependency injection container.
-   Multiple back-ends for session and cache storage.
-   Expressive, intuitive database ORM.
-   Database agnostic schema migrations.
-   Robust background job processing.
-   Real-time event broadcasting.

---

## 1) Giới thiệu ngắn gọn

Backend API quản lý lưu trữ đám mây (files/folders), chia sẻ nội bộ và public link, versioning, trash, tìm kiếm, thống kê cá nhân và quản trị hệ thống.

-   Ngôn ngữ/Framework: PHP 8.x, Laravel 11.x
-   Auth: Laravel Sanctum (Personal Access Token cho API; có thể mở rộng SPA cookies)

---

<!-- Gộp và tinh gọn từ architecture.md và app/Models/readme.md -->

## 2) Kiến trúc & Quy ước

Thư mục chuẩn trong `app/`

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

Luồng Request → Response

1. Client → Route → Controller action
2. Controller nhận DTO từ FormRequest (đã validate)
3. Controller gọi Service thực thi nghiệp vụ
4. Service tương tác Repository/Model; ném `DomainValidationException`/`ApiException` khi cần
5. Controller trả JSON chuẩn qua trait ApiResponse (`ok/created/noContent/fail`)

Quy ước Controller

-   Đặt dưới `App\Http\Controllers\Api\<Feature>`; kế thừa `BaseApiController`.
-   Mỗi feature có folder riêng kèm tài liệu và ví dụ HTTP:
    -   `<feature>.docs.md` — hướng dẫn nhanh, response mẫu, lỗi phổ biến.
    -   `<feature>.api.http` — request mẫu để kiểm thử nhanh.
-   Không chứa nghiệp vụ; chỉ gọi Service và trả `ok($data)`, `created($data)`, `noContent()`.

Quy ước Service

-   Một use case → một Service (hoặc nhóm liên quan).
-   Tên động từ rõ nghĩa: `RegisterUserService`, `UploadFileService`…
-   Không trực tiếp truy cập Request, không trả Response.
-   Ném `DomainValidationException` khi vi phạm nghiệp vụ.

Quy ước Repository

-   Mỗi aggregate/table chính có một Repository tương ứng.
-   Không chứa nghiệp vụ; chỉ truy vấn, lưu, phân trang, lọc.
-   Có thể thêm interface nếu cần thay thế dễ dàng.

Validation

-   Mọi endpoint phải có `FormRequest` riêng trong `Http/Requests`.
-   Override `failedValidation` để chuẩn hoá JSON 422.

Exception tập trung & Routing

-   Dùng `ApiException` (HTTP status tuỳ biến) và `DomainValidationException`.
-   Định tuyến trong `routes/api.php` nhóm theo middleware: public, `auth:sanctum`, admin (`can:admin`).

Middleware

-   `ForceJson`: ép header `Accept: application/json` để trả JSON nhất quán.
-   Đăng ký trong `bootstrap/app.php` qua `$middleware->append(...)`.

Quy ước đặt tên

-   Class PascalCase; method/biến camelCase; tên rõ nghĩa.
-   Service dùng động từ: `RegisterUserService`, `UploadFileService`…

---

## 3) Chuẩn Response & Exception

Trait/Helper

-   Trait: `App\Support\Traits\ApiResponse`
-   Helper: `api_ok($data, $meta = null)` (autoload trong `composer.json`)
-   Rendering lỗi chuẩn được cấu hình tại `bootstrap/app.php`.

Response thành công (chuẩn)

```json
{
    "success": true,
    "data": { "...": "..." },
    "error": null,
    "meta": null
}
```

Response lỗi (chuẩn)

```json
{
    "success": false,
    "data": null,
    "error": {
        "message": "...",
        "code": "...",
        "errors": { "field": ["..."] }
    },
    "meta": null
}
```

Validation 422

```json
{
    "success": false,
    "data": null,
    "error": {
        "message": "Validation failed",
        "code": "VALIDATION_ERROR",
        "errors": { "field": ["..."] }
    },
    "meta": null
}
```

Gợi ý mapping lỗi miền (domain): 400/404/409 tuỳ ngữ cảnh. Ném `DomainValidationException` với message rõ ràng.

---

## 4) Xác thực (Laravel Sanctum)

Hai chế độ phổ biến

-   Token-based (Personal Access Token – phù hợp mobile/server-to-server)
-   SPA Cookie-based (frontend cùng domain, cần CSRF cookie)

Yêu cầu tối thiểu

-   Model `User` dùng trait `Laravel\Sanctum\HasApiTokens`
-   Bảo vệ route bằng middleware `auth:sanctum`

Endpoints đã triển khai (Auth Controller: `App\Http\Controllers\Api\Auth\AuthController`)

-   `POST /api/auth/register` — body: `{ name, email, password, password_confirmation, device_name? }`
    -   Validate bởi `RegisterRequest`.
    -   Trả về: `token` và thông tin `user`.
    -   Email trùng: ném `ApiException(422, "EMAIL_TAKEN")`.
-   `POST /api/auth/login` — body: `{ email, password, device_name? }`
    -   Validate bởi `LoginRequest`.
    -   Sai thông tin: ném `ApiException(401, "INVALID_CREDENTIALS")`.
    -   Trả về: `token` và `user`.
-   `GET /api/auth/me` — yêu cầu `auth:sanctum`.
    -   Trả về: thông tin người dùng hiện tại.
-   `POST /api/auth/logout` — yêu cầu `auth:sanctum`.
    -   Thu hồi token hiện tại.
-   `POST /api/auth/logout-all` — yêu cầu `auth:sanctum`.
    -   Thu hồi tất cả token của người dùng.

Ví dụ cURL nhanh (điều chỉnh cho PowerShell nếu cần):

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

---

## 5) Danh sách Endpoints (tóm tắt)

Auth & Profile

-   POST /api/register — Đăng ký; POST /api/login — Đăng nhập; POST /api/auth/logout — Đăng xuất (auth)
-   POST /api/forgot-password — Gửi link reset; POST /api/reset-password — Reset password
-   POST /api/email/verify/{id} — Xác nhận email; POST /api/email/resend — Gửi lại xác nhận
-   GET /api/auth/me — User hiện tại (auth); GET /api/user — User hiện tại
-   PUT /api/user/profile — Cập nhật profile; PUT /api/user/password — Đổi mật khẩu

User Management (Admin)

-   CRUD /api/admin/users; cập nhật quota/role; xem storage-usage theo user

Files

-   Upload/list/show/download/update/delete/restore/force/copy/move
-   Recent, shared-with-me, shared-by-me

File Versions

-   Tạo/list/show/download/restore; delete version (admin)

Trash

-   Liệt kê files/folders trong trash, restore, xoá vĩnh viễn, empty trash

Folders

-   Tạo/list/show/contents/update/delete/restore/force/copy/move; tree; breadcrumb

Sharing & Public Links

-   Shares: tạo/list/show/update/delete, received, quản lý users & permission
-   Public links: tạo/list/show-by-token/update/delete/revoke, preview/download (public)

Search & Storage & Dashboard & Bulk

-   Search tổng/files/folders/suggestions; Storage usage/breakdown/limit
-   Admin storage overview/users; Dashboard (user/admin, stats)
-   Bulk: delete/move/copy/share/download nhiều file

Lưu ý: Các endpoint auth nằm dưới group auth:sanctum; admin thêm can:admin.

---

## 6) Checklist triển khai mỗi endpoint

1. Request validation
    - Tạo FormRequest trong App\\Http\\Requests; rules rõ ràng; authorize() nếu cần.
2. Service
    - Viết nghiệp vụ trong App\\Services; xử lý edge cases; ném DomainValidationException/ApiException.
3. Repository
    - Thêm phương thức truy vấn/lưu trong App\\Repositories; tối ưu index.
4. Controller
    - Inject Service; gọi xử lý; trả ok()/created()/noContent(). Không viết nghiệp vụ tại đây.
5. Routes
    - Khai báo đúng nhóm middleware trong routes/api.php (public/auth/admin).
6. Tests
    - Feature: happy path + 1-2 lỗi chính; Unit cho Service nếu phức tạp.
7. Docs
    - Bổ sung ví dụ request/response khi hoàn thiện endpoint.

Tạo file test .http ngay sau khi xong API

-   Sau khi viết xong 1 API, hãy tạo 1 file .http trong thư mục docs/ để test nhanh.
-   Quy ước tên file: <resource>.<action>.http, ví dụ: folders.store.http (tạo), folders.index.http (liệt kê).
-   Mẫu khởi tạo nhanh:

```http
@baseUrl = http://localhost:8000
@token = {{token}}

### Mô tả request
GET {{baseUrl}}/api/example
Accept: application/json
Authorization: Bearer {{token}}
```

Ví dụ hiện có:

-   docs/folders.store.http – test tạo thư mục
-   docs/folders.index.http – test liệt kê thư mục con

Auth & Admin

-   Dùng auth:sanctum cho route cần đăng nhập.
-   `can:admin` là placeholder: thêm Gate/Policy hoặc role check (ví dụ Gate `admin` kiểm tra user.role === 'admin').

---

## 7) Mô hình dữ liệu (Models) – Tổng quan đầy đủ

Tổng quan quan hệ

-   `User` 1—n `Folder`, 1—n `File`, 1—n `FileVersion`, 1—n `Share` (người tạo share)
-   `Folder` n—1 `User`, tự tham chiếu: `parent` (n—1), `children` (1—n), 1—n `File`, 1—n `Share`
-   `File` n—1 `User`, n—1 `Folder`, 1—n `FileVersion`, 1—n `Share`
-   `FileVersion` n—1 `File`, n—1 `User`
-   `Share` n—1 `User` (chủ sở hữu share), n—1 `File` (tuỳ chọn), n—1 `Folder` (tuỳ chọn), n—n `User` (người nhận) qua pivot `receives_shares`
-   `PublicLink` n—1 `User`, n—1 `File` (tuỳ chọn), n—1 `Folder` (tuỳ chọn)
-   `SystemConfig` dạng key-value

Pivot `receives_shares` (kỳ vọng): `share_id`, `user_id`, `permission` (quyền từng người nhận).

---

### User

```
fillable: [name, email, password, role, storage_limit, storage_used]
hidden: [password, remember_token]
casts: [email_verified_at => datetime, password => hashed, storage_limit => integer, storage_used => integer]
```

Quan hệ:

-   `files(): hasMany(File)` — các tệp người dùng sở hữu.
-   `folders(): hasMany(Folder)` — các thư mục người dùng sở hữu.
-   `shares(): hasMany(Share)` — các chia sẻ do người dùng tạo (chủ sở hữu share).
-   `fileVersions(): hasMany(FileVersion)` — các phiên bản tệp do người dùng tạo/thao tác.
-   `receivedShares(): belongsToMany(Share, 'receives_shares')->withPivot('permission')` — các share người dùng được nhận; quyền cụ thể nằm ở pivot.

Ràng buộc gợi ý:

-   `storage_used <= storage_limit` (nếu áp hạn mức lưu trữ).
-   `email` là duy nhất.

Ví dụ truy vấn:

```php
$user->files()->latest()->paginate(20);
$user->receivedShares()->with('file', 'folder')->get();
```

---

### Folder

```
fillable: [user_id, fol_folder_id, folder_name, is_deleted]
casts: [is_deleted => boolean]
traits: [SoftDeletes]
```

Quan hệ:

-   `user(): belongsTo(User)` — chủ sở hữu.
-   `parent(): belongsTo(Folder, 'fol_folder_id')` — thư mục cha (có thể null nếu root).
-   `children(): hasMany(Folder, 'fol_folder_id')` — thư mục con.
-   `files(): hasMany(File)` — các tệp trong thư mục.
-   `shares(): hasMany(Share)` — các chia sẻ gắn với thư mục.

Ràng buộc/invariant gợi ý:

-   `fol_folder_id` null với thư mục gốc; nếu có, `parent.user_id === user_id`.
-   Tránh chu kỳ: không cho `parent` trỏ về chính nó hoặc con cháu của nó.
-   Đồng bộ `is_deleted` và `deleted_at` (soft delete) với quy tắc rõ ràng.

Ví dụ truy vấn:

```php
$folder->children()->withCount('files')->get();
$folder->files()->where('is_deleted', false)->get();
```

---

### File

```
fillable: [folder_id, user_id, display_name, file_size, mime_type, file_extension, is_deleted, last_opened_at]
casts: [file_size => integer, is_deleted => boolean, last_opened_at => datetime]
traits: [SoftDeletes]
```

Quan hệ:

-   `user(): belongsTo(User)` — chủ sở hữu tệp.
-   `folder(): belongsTo(Folder)` — thư mục chứa.
-   `versions(): hasMany(FileVersion)` — các phiên bản của tệp.
-   `shares(): hasMany(Share)` — các chia sẻ gắn với tệp.

Ràng buộc/invariant gợi ý:

-   `file_size >= 0`.
-   `user_id` của `File` nên trùng với `folder.user_id` để đảm bảo sở hữu nhất quán.
-   Quy ước rõ giữa `is_deleted` và `deleted_at`.

Ví dụ truy vấn:

```php
$file->versions()->orderByDesc('version_number')->first();
File::whereBelongsTo($user)->where('is_deleted', false)->paginate(50);
```

---

### FileVersion

```
fillable: [file_id, user_id, version_number, uuid, file_extension, mime_type, file_size, action, notes]
casts: [version_number => integer, file_size => integer]
```

Quan hệ:

-   `file(): belongsTo(File)` — tệp gốc.
-   `user(): belongsTo(User)` — người thực hiện hành động tạo phiên bản.

Ràng buộc/invariant gợi ý:

-   `uuid` duy nhất theo hệ thống.
-   `version_number` tăng dần trong phạm vi một `file_id` (unique `(file_id, version_number)`).
-   `file_extension`, `mime_type` phản ánh đúng nội dung; `file_size >= 0`.

Ví dụ truy vấn:

```php
FileVersion::where('file_id', $fileId)->orderBy('version_number')->get();
```

---

### Share

```
fillable: [file_id, folder_id, user_id, shareable_type, permission]
```

Quan hệ:

-   `user(): belongsTo(User)` — chủ sở hữu share (người tạo chia sẻ).
-   `file(): belongsTo(File)` — mục tiêu là file (tuỳ chọn).
-   `folder(): belongsTo(Folder)` — mục tiêu là folder (tuỳ chọn).
-   `receivers(): belongsToMany(User, 'receives_shares')->withPivot('permission')` — người nhận share (n—n) với quyền ở pivot.

Ràng buộc/invariant gợi ý:

-   Chính xác một trong hai: `file_id` hoặc `folder_id` khác null.
-   `shareable_type` phản ánh mục tiêu (`file`/`folder`) và đồng bộ với trường id tương ứng.
-   `permission` là enum có kiểm soát (ví dụ: `view`, `edit`, `owner`...).

Ví dụ truy vấn:

```php
$share->receivers()->wherePivot('permission', 'view')->get();
Share::with(['file', 'folder', 'receivers'])->latest()->paginate();
```

---

### PublicLink

```
fillable: [user_id, folder_id, file_id, shareable_type, permission, token, expired_at, revoked_at]
casts: [expired_at => datetime, revoked_at => datetime]
```

Quan hệ:

-   `user(): belongsTo(User)` — người tạo link công khai.
-   `file(): belongsTo(File)` — nếu link cho file.
-   `folder(): belongsTo(Folder)` — nếu link cho folder.

Ràng buộc/invariant gợi ý:

-   `token` duy nhất, đủ độ dài ngẫu nhiên.
-   Một trong hai: `file_id` hoặc `folder_id` được set; `shareable_type` đồng bộ.
-   `revoked_at` khác null khi link bị thu hồi; `expired_at` ở tương lai (hoặc null nếu vô hạn).

Ví dụ truy vấn:

```php
PublicLink::whereNull('revoked_at')->where(function($q){
    $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
})->get();
```

---

### SystemConfig

```
fillable: [config_key, config_value]
```

Gợi ý sử dụng:

-   Lưu cấu hình mức hệ thống: hạn mức mặc định, tính năng bật/tắt.
-   `config_key` nên unique; `config_value` có thể là JSON/string (chuẩn hoá khi dùng).

Ví dụ truy vấn:

```php
SystemConfig::where('config_key', 'default_storage_limit')->value('config_value');
```

---

## 8) Kiểm thử nhanh (ví dụ tạo Folder)

Giả sử đã có PAT `TOKEN` từ /api/login.

POST /api/folders
Body JSON

-   folder_name: string, required, max 255
-   parent_folder_id: integer|null

curl mẫu (PowerShell chỉnh token/host cho phù hợp):

```bash
BASE=http://localhost:8000
TOKEN="<PAT>"

curl -sS -X POST "$BASE/api/folders" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{
        "folder_name": "Tai lieu",
        "parent_folder_id": null
    }'
```

Kết quả thành công: 200 + payload thư mục mới. Lỗi phổ biến: 422 (validation), lỗi miền (VD: parent không thuộc user → 400/404 tuỳ quy ước).
