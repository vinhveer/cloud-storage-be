### File

API quản lý file: upload, download, list, update, delete, restore.

#### Endpoints

**POST /api/files** - Upload file mới
**GET /api/files** - Danh sách file
**GET /api/files/recent** - File gần đây
**GET /api/files/shared-with-me** - File được chia sẻ cho tôi
**GET /api/files/shared-by-me** - File tôi chia sẻ
**GET /api/files/{id}** - Chi tiết file
**GET /api/files/{id}/download** - Tải file
**PUT /api/files/{id}** - Cập nhật file
**DELETE /api/files/{id}** - Xóa file (soft delete)
**POST /api/files/{id}/restore** - Khôi phục file
**DELETE /api/files/{id}/force** - Xóa vĩnh viễn
**POST /api/files/{id}/copy** - Copy file
**POST /api/files/{id}/move** - Di chuyển file

#### File Versions

**POST /api/files/{id}/versions** - Upload phiên bản mới
**GET /api/files/{id}/versions** - Danh sách phiên bản
**GET /api/files/{id}/versions/{versionId}** - Chi tiết phiên bản
**GET /api/files/{id}/versions/{versionId}/download** - Tải phiên bản
**POST /api/files/{id}/versions/{versionId}/restore** - Khôi phục phiên bản
**DELETE /api/files/{id}/versions/{versionId}** - Xóa phiên bản

#### Bulk Operations

**POST /api/files/bulk-delete** - Xóa nhiều file
**POST /api/files/bulk-move** - Di chuyển nhiều file
**POST /api/files/bulk-copy** - Copy nhiều file
**POST /api/files/bulk-share** - Chia sẻ nhiều file
**POST /api/files/bulk-download** - Tải nhiều file

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "document.pdf",
    "size": 1024,
    "mime_type": "application/pdf"
  },
  "error": null,
  "meta": null
}
```

