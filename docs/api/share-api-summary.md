# Share API - Tóm tắt nhanh

## Endpoints chính

| Method | Endpoint | Mô tả | Auth |
|--------|----------|-------|------|
| POST | `/api/shares` | Tạo share mới | ✅ |
| GET | `/api/shares` | Danh sách share đã tạo | ✅ |
| GET | `/api/shares/received` | Danh sách share nhận được | ✅ |
| GET | `/api/shares/{id}` | Chi tiết share | ✅ |
| DELETE | `/api/shares/{id}` | Xóa share | ✅ |
| POST | `/api/shares/{id}/users` | Thêm user vào share | ✅ (⚠️ chưa active trong routes) |
| DELETE | `/api/shares/{id}/users/{userId}` | Xóa user khỏi share | ✅ |

## Request/Response mẫu

### 1. Tạo Share

**Request:**
```json
POST /api/shares
{
  "shareable_type": "file",
  "shareable_id": 58,
  "user_ids": [10, 11],
  "permission": "edit"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "share": {
      "share_id": 56,
      "shareable_type": "file",
      "shareable_id": 58,
      "shared_with": [
        { "user_id": 10, "name": "User A", "permission": "edit" }
      ]
    },
    "share_created": true,
    "added_user_ids": [10, 11]
  }
}
```

### 2. Danh sách Share

**Request:**
```
GET /api/shares?page=1&per_page=15
```

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "share_id": 56,
        "shareable_type": "file",
        "shareable_name": "document.pdf",
        "shared_with_count": 3
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_items": 72
    }
  }
}
```

### 3. Chi tiết Share

**Request:**
```
GET /api/shares/56
```

**Response:**
```json
{
  "share_id": 56,
  "shareable_type": "file",
  "shareable_name": "document.pdf",
  "shared_by": { "user_id": 1, "name": "Owner" },
  "shared_with": [
    { "user_id": 10, "name": "User A", "permission": "edit" }
  ]
}
```

## Permission Levels

- `view`: Chỉ xem
- `download`: Xem và tải xuống
- `edit`: Xem, tải xuống và chỉnh sửa

## Error Codes

- `401`: Unauthenticated
- `403`: Forbidden (không sở hữu resource)
- `404`: Not found
- `422`: Validation failed

## Lưu ý tích hợp

1. **Authentication**: Tất cả endpoints cần Bearer token
2. **Pagination**: Mặc định 15 items/page, tối đa 100
3. **Duplicate Prevention**: Hệ thống tự động ngăn thêm user trùng
4. **Soft Delete**: Share sẽ ẩn nếu file/folder bị xóa
5. **Permission Update**: Tạo lại share với cùng user sẽ cập nhật quyền

## Flow tích hợp cơ bản

1. **Tạo Share**: User chọn file/folder → chọn users → chọn permission → POST `/api/shares`
2. **Xem Share đã tạo**: GET `/api/shares` → hiển thị danh sách
3. **Xem Share nhận được**: GET `/api/shares/received` → hiển thị danh sách
4. **Chi tiết Share**: Click vào share → GET `/api/shares/{id}` → hiển thị chi tiết
5. **Quản lý Share**: Thêm/xóa user, xóa share → các endpoints tương ứng

