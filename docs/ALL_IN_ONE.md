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

## Models README (d:\Study\MNM\cloud-storage-be\app\Models\README.md)

(Full content included from source)

## Tài liệu Models (App\\Models)

Mục tiêu: mô tả chi tiết các model, trường dữ liệu, quan hệ, ràng buộc và ví dụ truy vấn, để phát triển tính năng an toàn và nhất quán.

[content truncated for brevity]

---

## 2) Kiến trúc & Quy ước

-   Controller mảnh (App\\Http\\Controllers\\Api): chỉ nhận request, gọi Service, trả JSON chuẩn.
-   Service mạnh (App\\Services): chứa nghiệp vụ, gọi Repository; không truy cập Request, không trả Response.
-   Repository (App\\Repositories): bọc truy cập DB (Eloquent/Query Builder), không chứa nghiệp vụ.
-   Validation (App\\Http\\Requests): mỗi endpoint có FormRequest riêng; sai trả 422 JSON.
-   Exception tập trung (App\\Exceptions): DomainValidationException, ApiException; render JSON thống nhất qua cấu hình.
-   Middleware gợi ý: ForceJson (ép Accept: application/json) để response nhất quán.
-   Định tuyến: routes/api.php nhóm theo middleware: public, auth:sanctum, admin (can:admin – cần triển khai Gate/Role).

Quy ước đặt tên

-   Class PascalCase; method/biến camelCase; tên rõ nghĩa.
-   Service dùng động từ: RegisterUserService, UploadFileService…

---

## 3) Chuẩn Response & Exception

Thành công

-   success: true
-   data: object/array (payload)
-   error: null
-   meta: optional

Lỗi

-   success: false
-   data: null
-   error: { message, code, errors? }

Validation 422

-   error: { message: 'Validation failed', code: 'VALIDATION_ERROR', errors: { field: [..] } }

Domain rules

-   Ném DomainValidationException với message rõ ràng; map HTTP code hợp lý (400/404/409…).

---

## 4) Xác thực (Laravel Sanctum)

Hai chế độ phổ biến

-   Token-based (Personal Access Token – phù hợp mobile/server-to-server)
-   SPA Cookie-based (frontend cùng domain, cần CSRF cookie)

Yêu cầu tối thiểu

-   Model User dùng trait Laravel\\Sanctum\\HasApiTokens
-   Bảo vệ route bằng middleware auth:sanctum

Luồng Token-based điển hình

1. POST /api/login → xác thực và trả token
2. Gọi API kèm Authorization: Bearer <token>
3. POST /api/auth/logout (thu hồi token hiện tại) hoặc /api/auth/logout-all (thu hồi tất cả)

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

## 7) Mô hình dữ liệu (Models) – Tổng quan

User

-   Quan hệ: hasMany(Folder, File, FileVersion, Share), belongsToMany(Share qua receives_shares) như người nhận.
-   Trường quan trọng: name, email (unique), password (hashed), role, storage_limit, storage_used.

Folder

-   Trường: user_id, fol_folder_id (parent), folder_name, is_deleted; SoftDeletes.
-   Quan hệ: belongsTo(User), belongsTo(parent Folder), hasMany(children), hasMany(File), hasMany(Share).
-   Ràng buộc: parent cùng owner; tránh chu kỳ; quy ước đồng bộ is_deleted và deleted_at.

File

-   Trường: folder_id, user_id, display_name, file_size, mime_type, file_extension, is_deleted, last_opened_at; SoftDeletes.
-   Quan hệ: belongsTo(User), belongsTo(Folder), hasMany(FileVersion), hasMany(Share).
-   Ràng buộc: file_size >= 0; owner file trùng owner folder.

FileVersion

-   Trường: file_id, user_id, version_number, uuid, file_extension, mime_type, file_size, action, notes.
-   Ràng buộc: uuid unique; (file_id, version_number) unique; version_number tăng dần.

Share

-   Trường: file_id | folder_id (một trong hai), user_id (owner), shareable_type, permission.
-   Quan hệ: belongsTo(User), belongsTo(File/Folder), belongsToMany(User receivers) withPivot('permission').

PublicLink

-   Trường: user_id, file_id|folder_id, shareable_type, permission, token, expired_at, revoked_at.
-   Ràng buộc: token unique; expired_at tương lai hoặc null; revoked_at khi thu hồi.

SystemConfig

-   Key-value cho cấu hình hệ thống: max_file_size, allowed_extensions, default_storage_limit…

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
