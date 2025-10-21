### Folder

API quản lý thư mục: tạo, list, update, delete, restore.

#### Endpoints

**POST /api/folders** - Tạo folder mới
**GET /api/folders** - Danh sách folder
**GET /api/folders/tree** - Cây thư mục
**GET /api/folders/{id}** - Chi tiết folder
**GET /api/folders/{id}/contents** - Nội dung folder
**GET /api/folders/{id}/breadcrumb** - Breadcrumb
**PUT /api/folders/{id}** - Cập nhật folder
**DELETE /api/folders/{id}** - Xóa folder (soft delete)
**POST /api/folders/{id}/restore** - Khôi phục folder
**DELETE /api/folders/{id}/force** - Xóa vĩnh viễn
**POST /api/folders/{id}/copy** - Copy folder
**POST /api/folders/{id}/move** - Di chuyển folder

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "folder_id": 1,
    "folder_name": "Documents",
    "fol_folder_id": null,
    "created_at": "2025-01-01T00:00:00.000000Z"
  },
  "error": null,
  "meta": null
}
```

