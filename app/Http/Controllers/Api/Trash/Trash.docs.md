### Trash

API quản lý thùng rác: list, restore, xóa vĩnh viễn.

#### Endpoints

**GET /api/trash/files** - Danh sách file trong trash
**GET /api/trash/folders** - Danh sách folder trong trash
**POST /api/trash/{id}/restore** - Khôi phục item
**DELETE /api/trash/{id}** - Xóa vĩnh viễn item
**DELETE /api/trash/empty** - Làm trống thùng rác

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "files": [],
    "folders": []
  },
  "error": null,
  "meta": null
}
```

