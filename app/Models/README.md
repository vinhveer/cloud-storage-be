## Tài liệu Models (App\\Models)

Mục tiêu: mô tả chi tiết các model, trường dữ liệu, quan hệ, ràng buộc và ví dụ truy vấn, để phát triển tính năng an toàn và nhất quán.

### Tổng quan quan hệ

- `User` 1—n `Folder`, 1—n `File`, 1—n `FileVersion`, 1—n `Share` (người tạo share)
- `Folder` n—1 `User`, tự tham chiếu: `parent` (n—1), `children` (1—n), 1—n `File`, 1—n `Share`
- `File` n—1 `User`, n—1 `Folder`, 1—n `FileVersion`, 1—n `Share`
- `FileVersion` n—1 `File`, n—1 `User`
- `Share` n—1 `User` (chủ sở hữu share), n—1 `File` (tuỳ chọn), n—1 `Folder` (tuỳ chọn), n—n `User` (người nhận) qua pivot `receives_shares`
- `PublicLink` n—1 `User`, n—1 `File` (tuỳ chọn), n—1 `Folder` (tuỳ chọn)
- `SystemConfig` cấu hình hệ thống dạng key-value, không quan hệ ngoại khoá tới các model trên

Pivot `receives_shares` (kỳ vọng): `share_id`, `user_id`, `permission` (quyền từng người nhận).

---

### User

```php
fillable: [name, email, password, role, storage_limit, storage_used]
hidden: [password, remember_token]
casts: [email_verified_at => datetime, password => hashed, storage_limit => integer, storage_used => integer]
```

Quan hệ:
- `files(): hasMany(File)` — các tệp người dùng sở hữu.
- `folders(): hasMany(Folder)` — các thư mục người dùng sở hữu.
- `shares(): hasMany(Share)` — các chia sẻ do người dùng tạo (chủ sở hữu share).
- `fileVersions(): hasMany(FileVersion)` — các phiên bản tệp do người dùng tạo/thao tác.
- `receivedShares(): belongsToMany(Share, 'receives_shares')->withPivot('permission')` — các share người dùng được nhận; quyền cụ thể nằm ở pivot.

Ràng buộc gợi ý:
- `storage_used <= storage_limit` (nếu áp hạn mức lưu trữ).
- `email` là duy nhất.

Ví dụ truy vấn:
```php
$user->files()->latest()->paginate(20);
$user->receivedShares()->with('file', 'folder')->get();
```

---

### Folder

```php
fillable: [user_id, fol_folder_id, folder_name, is_deleted]
casts: [is_deleted => boolean]
traits: [SoftDeletes]
```

Quan hệ:
- `user(): belongsTo(User)` — chủ sở hữu.
- `parent(): belongsTo(Folder, 'fol_folder_id')` — thư mục cha (có thể null nếu root).
- `children(): hasMany(Folder, 'fol_folder_id')` — thư mục con.
- `files(): hasMany(File)` — các tệp trong thư mục.
- `shares(): hasMany(Share)` — các chia sẻ gắn với thư mục.

Ràng buộc/invariant gợi ý:
- `fol_folder_id` null với thư mục gốc; nếu có, `parent.user_id === user_id`.
- Tránh chu kỳ: không cho `parent` trỏ về chính nó hoặc con cháu của nó.
- Dùng đồng thời `SoftDeletes` (cột `deleted_at`) và `is_deleted` (cờ logic): định nghĩa rõ quy tắc đồng bộ hai trạng thái xoá.

Ví dụ truy vấn:
```php
$folder->children()->withCount('files')->get();
$folder->files()->where('is_deleted', false)->get();
```

---

### File

```php
fillable: [folder_id, user_id, display_name, file_size, mime_type, file_extension, is_deleted, last_opened_at]
casts: [file_size => integer, is_deleted => boolean, last_opened_at => datetime]
traits: [SoftDeletes]
```

Quan hệ:
- `user(): belongsTo(User)` — chủ sở hữu tệp.
- `folder(): belongsTo(Folder)` — thư mục chứa.
- `versions(): hasMany(FileVersion)` — các phiên bản của tệp.
- `shares(): hasMany(Share)` — các chia sẻ gắn với tệp.

Ràng buộc/invariant gợi ý:
- `file_size >= 0`.
- `user_id` của `File` nên trùng với `folder.user_id` để đảm bảo sở hữu nhất quán.
- `is_deleted` và `deleted_at` (soft delete) cần quy ước rõ (ví dụ: `is_deleted` cho UI, `deleted_at` cho logic loại trừ mặc định của Eloquent).

Ví dụ truy vấn:
```php
$file->versions()->orderByDesc('version_number')->first();
File::whereBelongsTo($user)->where('is_deleted', false)->paginate(50);
```

---

### FileVersion

```php
fillable: [file_id, user_id, version_number, uuid, file_extension, mime_type, file_size, action, notes]
casts: [version_number => integer, file_size => integer]
```

Quan hệ:
- `file(): belongsTo(File)` — tệp gốc.
- `user(): belongsTo(User)` — người thực hiện hành động tạo phiên bản.

Ràng buộc/invariant gợi ý:
- `uuid` duy nhất theo hệ thống.
- `version_number` tăng dần trong phạm vi một `file_id` (có thể unique composite `(file_id, version_number)`).
- `file_extension`, `mime_type` phản ánh đúng nội dung lưu trữ, `file_size >= 0`.

Ví dụ truy vấn:
```php
FileVersion::where('file_id', $fileId)->orderBy('version_number')->get();
```

---

### Share

```php
fillable: [file_id, folder_id, user_id, shareable_type, permission]
```

Quan hệ:
- `user(): belongsTo(User)` — chủ sở hữu share (người tạo chia sẻ).
- `file(): belongsTo(File)` — mục tiêu chia sẻ là file (tuỳ chọn).
- `folder(): belongsTo(Folder)` — mục tiêu chia sẻ là folder (tuỳ chọn).
- `receivers(): belongsToMany(User, 'receives_shares')->withPivot('permission')` — người nhận share (n—n) với quyền ở pivot.

Ràng buộc/invariant gợi ý:
- Chính xác một trong hai: `file_id` hoặc `folder_id` khác null.
- `shareable_type` phản ánh mục tiêu (`file`/`folder`) và đồng bộ với trường id tương ứng.
- `permission` là enum có kiểm soát (ví dụ: `view`, `edit`, `owner`...).
- Pivot `receives_shares`: cột tối thiểu `share_id`, `user_id`, kèm `permission` (ghi đè quyền mặc định nếu có).

Ví dụ truy vấn:
```php
$share->receivers()->wherePivot('permission', 'view')->get();
Share::with(['file', 'folder', 'receivers'])->latest()->paginate();
```

---

### PublicLink

```php
fillable: [user_id, folder_id, file_id, shareable_type, permission, token, expired_at, revoked_at]
casts: [expired_at => datetime, revoked_at => datetime]
```

Quan hệ:
- `user(): belongsTo(User)` — người tạo link công khai.
- `file(): belongsTo(File)` — nếu link cho file.
- `folder(): belongsTo(Folder)` — nếu link cho folder.

Ràng buộc/invariant gợi ý:
- `token` duy nhất, đủ độ dài ngẫu nhiên.
- Một trong hai: `file_id` hoặc `folder_id` được set; `shareable_type` đồng bộ.
- `revoked_at` khác null khi link bị thu hồi; validation `expired_at` trong tương lai (hoặc null nếu vô hạn).

Ví dụ truy vấn:
```php
PublicLink::whereNull('revoked_at')->where(function($q){
    $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
})->get();
```

---

### SystemConfig

```php
fillable: [config_key, config_value]
```

Gợi ý sử dụng:
- Lưu cấu hình mức hệ thống: hạn mức mặc định, tính năng bật/tắt.
- `config_key` nên unique; `config_value` có thể là JSON/string (cần chuẩn hoá khi dùng).

Ví dụ truy vấn:
```php
SystemConfig::where('config_key', 'default_storage_limit')->value('config_value');
```

---

### Ghi chú thực thi & best practices

- Với `SoftDeletes`, mặc định Eloquent loại trừ bản ghi xoá mềm. Dùng `withTrashed()`/`onlyTrashed()` khi cần.
- Nếu dùng cả `is_deleted` và `deleted_at`, chuẩn hoá luồng xoá/khôi phục để tránh lệch trạng thái.
- Đảm bảo index/unique phù hợp: email của `User`, `(file_id, version_number)` của `FileVersion`, `token` của `PublicLink`.
- Kiểm thử quan hệ chéo chủ sở hữu: `File.user_id === Folder.user_id`.
- Rõ ràng về enum `permission` và `shareable_type` (validate ở FormRequest/DB check constraint nếu có).


