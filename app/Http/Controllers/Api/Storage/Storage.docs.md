### Storage

API thống kê dung lượng lưu trữ.

#### Endpoints

**GET /api/storage/usage** - Dung lượng đã dùng
**GET /api/storage/breakdown** - Phân tích dung lượng theo loại
**GET /api/storage/limit** - Giới hạn dung lượng

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "used": 1024000,
    "limit": 10737418240,
    "percentage": 0.01
  },
  "error": null,
  "meta": null
}
```

