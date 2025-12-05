# Share API Documentation

## Tổng quan

API Share cho phép người dùng chia sẻ file hoặc folder với các user khác trong hệ thống. Mỗi share có thể có nhiều người nhận, mỗi người nhận có quyền riêng (view, download, edit).

## Authentication

Tất cả endpoints yêu cầu Bearer token trong header:
```
Authorization: Bearer {token}
```

## Endpoints

### 1. Tạo Share mới

**POST** `/api/shares`

Tạo share mới hoặc cập nhật share đã tồn tại cho cùng một file/folder.

**Request Body:**
```json
{
  "shareable_type": "file|folder",
  "shareable_id": 123,
  "user_ids": [10, 11, 12],
  "permission": "view|download|edit"
}
```

**Validation:**
- `shareable_type`: required, chỉ nhận `file` hoặc `folder`
- `shareable_id`: required, integer, phải tồn tại và thuộc về user hiện tại
- `user_ids`: required, array, tối thiểu 1 phần tử, mỗi phần tử là integer distinct
- `permission`: required, chỉ nhận `view`, `download`, hoặc `edit`

**Response 201 (Created):**
```json
{
  "success": true,
  "data": {
    "share": {
      "share_id": 56,
      "shareable_type": "file",
      "shareable_id": 58,
      "user_id": 1,
      "created_at": "2024-01-15 10:30:00",
      "shared_with": [
        {
          "user_id": 10,
          "name": "Nguyễn Văn A",
          "permission": "edit"
        },
        {
          "user_id": 11,
          "name": "Trần Thị B",
          "permission": "edit"
        }
      ]
    },
    "share_created": true,
    "added_user_ids": [10, 11],
    "updated_user_ids": [],
    "skipped_user_ids": []
  },
  "error": null,
  "meta": null
}
```

**Lưu ý:**
- Nếu share đã tồn tại cho cùng file/folder, sẽ tái sử dụng share đó
- `share_created`: `true` nếu tạo mới, `false` nếu dùng lại share cũ
- `added_user_ids`: danh sách user được thêm mới
- `updated_user_ids`: danh sách user có quyền được cập nhật
- `skipped_user_ids`: danh sách user đã tồn tại với cùng quyền

**Error Responses:**
- `401`: Unauthenticated
- `403`: Không sở hữu file/folder
- `404`: File/folder không tồn tại hoặc đã bị xóa
- `422`: Validation failed hoặc user_ids không tồn tại

---

### 2. Danh sách Share đã tạo

**GET** `/api/shares`

Lấy danh sách các share mà user hiện tại đã tạo.

**Query Parameters:**
- `page` (optional): số trang, mặc định 1
- `per_page` (optional): số item mỗi trang, mặc định 15, tối đa 100

**Response 200:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "share_id": 56,
        "shareable_type": "file",
        "shareable_name": "document.pdf",
        "shared_with_count": 3,
        "created_at": "2024-01-15 10:30:00"
      },
      {
        "share_id": 57,
        "shareable_type": "folder",
        "shareable_name": "Projects",
        "shared_with_count": 2,
        "created_at": "2024-01-14 09:20:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_items": 72
    }
  },
  "error": null,
  "meta": null
}
```

**Error Responses:**
- `401`: Unauthenticated

---

### 3. Chi tiết Share

**GET** `/api/shares/{id}`

Lấy thông tin chi tiết của một share, bao gồm danh sách người nhận và quyền của từng người.

**Path Parameters:**
- `id`: share_id

**Response 200:**
```json
{
  "share_id": 56,
  "shareable_type": "file",
  "shareable_name": "document.pdf",
  "created_at": "2024-01-15 10:30:00",
  "shared_by": {
    "user_id": 1,
    "name": "Admin User"
  },
  "shared_with": [
    {
      "user_id": 10,
      "name": "Nguyễn Văn A",
      "permission": "edit"
    },
    {
      "user_id": 11,
      "name": "Trần Thị B",
      "permission": "view"
    }
  ]
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Share không tồn tại, không phải owner, hoặc file/folder đã bị xóa

---

### 4. Danh sách Share nhận được

**GET** `/api/shares/received`

Lấy danh sách các share mà user hiện tại đã nhận được từ người khác.

**Query Parameters:**
- `page` (optional): số trang, mặc định 1
- `per_page` (optional): số item mỗi trang, mặc định 15, tối đa 100

**Response 200:**
```json
{
  "data": [
    {
      "share_id": 56,
      "shareable_type": "file",
      "shareable_name": "document.pdf",
      "owner": {
        "user_id": 1,
        "name": "Admin User"
      },
      "permission": "edit",
      "shared_at": "2024-01-15 10:30:00"
    },
    {
      "share_id": 57,
      "shareable_type": "folder",
      "shareable_name": "Projects",
      "owner": {
        "user_id": 2,
        "name": "Manager User"
      },
      "permission": "view",
      "shared_at": "2024-01-14 09:20:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 3,
    "total_items": 25
  }
}
```

**Error Responses:**
- `401`: Unauthenticated

---

### 5. Xóa Share

**DELETE** `/api/shares/{id}`

Xóa một share và thu hồi quyền truy cập của tất cả người nhận.

**Path Parameters:**
- `id`: share_id

**Response 200:**
```json
{
  "success": true,
  "message": "Share revoked successfully."
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Share không tồn tại hoặc không phải owner

---

### 6. Thêm User vào Share

**POST** `/api/shares/{id}/users`

**Lưu ý**: Endpoint này có thể chưa được kích hoạt trong routes. Kiểm tra `routes/api.php` để xác nhận.

Thêm một hoặc nhiều user vào share đã tồn tại.

**Path Parameters:**
- `id`: share_id

**Request Body:**
```json
{
  "user_ids": [10, 11],
  "permission": "view|download|edit"
}
```

**Validation:**
- `user_ids`: required, array, tối thiểu 1 phần tử, mỗi phần tử là integer distinct
- `permission`: required, chỉ nhận `view`, `download`, hoặc `edit`

**Response 200:**
```json
{
  "success": true,
  "message": "Users added to share successfully.",
  "added_users": [
    {
      "user_id": 10,
      "name": "Nguyễn Văn A",
      "permission": "view"
    },
    {
      "user_id": 11,
      "name": "Trần Thị B",
      "permission": "view"
    }
  ]
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Share không tồn tại hoặc không phải owner
- `422`: Validation failed, user_ids không tồn tại, hoặc tất cả user đã được thêm trước đó

**Lưu ý:**
- Chỉ owner của share mới có thể thêm user
- User đã tồn tại trong share sẽ không được thêm lại
- Response chỉ trả về danh sách user được thêm thành công

---

### 7. Xóa User khỏi Share

**DELETE** `/api/shares/{id}/users/{userId}`

Xóa một user khỏi share, thu hồi quyền truy cập của user đó.

**Path Parameters:**
- `id`: share_id
- `userId`: user_id cần xóa

**Response 200:**
```json
{
  "success": true,
  "message": "User removed from share."
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Share không tồn tại, không phải owner, hoặc user không có trong share

---

## Permission Levels

- `view`: Chỉ xem, không thể tải xuống hoặc chỉnh sửa
- `download`: Xem và tải xuống, không thể chỉnh sửa
- `edit`: Xem, tải xuống và chỉnh sửa

## Lưu ý quan trọng

1. **Ownership**: Chỉ owner của file/folder mới có thể tạo và quản lý share
2. **Soft Delete**: Share sẽ không hiển thị nếu file/folder đã bị xóa (soft delete)
3. **Duplicate Prevention**: Hệ thống tự động ngăn việc thêm user trùng lặp
4. **Permission Update**: Khi tạo share với cùng file/folder và user đã tồn tại, quyền sẽ được cập nhật nếu khác nhau
5. **Self Share**: User không thể share file/folder cho chính mình (sẽ bị loại bỏ tự động)

