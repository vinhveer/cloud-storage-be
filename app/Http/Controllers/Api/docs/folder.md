## FolderController — Hướng dẫn nhanh & kiểm thử

### Endpoint

- `POST /api/folders`

### Mục đích

Tạo thư mục mới trong thư mục cha (hoặc root nếu không truyền `parent_folder_id`).

### Body (JSON)

```json
{
  "folder_name": "Tên thư mục",
  "parent_folder_id": 123
}
```

- `folder_name`: string, bắt buộc, tối đa 255.
- `parent_folder_id`: số nguyên, tuỳ chọn; nếu null/không gửi → tạo ở root.

### Response mẫu (200)

```json
{
  "success": true,
  "data": {
    "message": "Folder created successfully.",
    "folder": {
      "folder_id": 24,
      "folder_name": "Tài liệu học kỳ 1",
      "fol_folder_id": null,
      "user_id": 12,
      "created_at": "2025-10-12T14:30:22Z"
    }
  },
  "error": null,
  "meta": null
}
```

### Lỗi phổ biến

- 422 Validation:

```json
{
  "success": false,
  "data": null,
  "error": {
    "message": "Validation failed",
    "code": "VALIDATION_ERROR",
    "errors": {"folder_name": ["The folder name field is required."]}
  },
  "meta": null
}
```

- 404 Domain (parent không tồn tại/không thuộc user):

```json
{
  "success": false,
  "data": null,
  "error": {
    "message": "Parent folder not found or not owned by user",
    "code": "DOMAIN_VALIDATION",
    "errors": null
  },
  "meta": null
}
```

### cURL kiểm thử

Chỉnh sửa `BASE` và `TOKEN` cho phù hợp (nếu dùng Sanctum/JWT):

```bash
BASE=http://localhost:8000
TOKEN="YOUR_BEARER_TOKEN"

# 1) Tạo folder ở root (thành công)
curl -sS -X POST "$BASE/api/folders" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "folder_name": "Tài liệu học kỳ 1"
  }' | jq .

# 2) Tạo folder trong parent hợp lệ (thành công)
PARENT_ID=10
curl -sS -X POST "$BASE/api/folders" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{\"folder_name\": \"Bài tập\", \"parent_folder_id\": $PARENT_ID}" | jq .

# 3) Thiếu folder_name (422)
curl -sS -X POST "$BASE/api/folders" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "parent_folder_id": 1
  }' | jq .

# 4) parent_folder_id không thuộc user/không tồn tại (404)
curl -sS -X POST "$BASE/api/folders" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "folder_name": "Tài liệu học kỳ 2",
    "parent_folder_id": 999999
  }' | jq .

# 5) folder_name quá dài (422)
LONG_NAME=$(printf 'a%.0s' {1..300})
curl -sS -X POST "$BASE/api/folders" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{\"folder_name\": \"$LONG_NAME\"}" | jq .
```


