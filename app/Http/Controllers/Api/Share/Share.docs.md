### Share

API chia sẻ file/folder cho user khác.

#### Endpoints

**POST /api/shares** - Tạo share mới
**GET /api/shares** - Danh sách share của tôi
**GET /api/shares/received** - Share nhận được
**GET /api/shares/{id}** - Chi tiết share
**PUT /api/shares/{id}** - Cập nhật share
**DELETE /api/shares/{id}** - Xóa share
**POST /api/shares/{id}/users** - Thêm user vào share
**DELETE /api/shares/{id}/users/{userId}** - Xóa user khỏi share
**PUT /api/shares/{id}/users/{userId}** - Cập nhật quyền user

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "id": 1,
    "resource_type": "file",
    "resource_id": 123,
    "permission": "read",
    "users": []
  },
  "error": null,
  "meta": null
}
```

