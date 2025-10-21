### Search

API tìm kiếm file và folder.

#### Endpoints

**GET /api/search** - Tìm kiếm chung
**GET /api/search/files** - Tìm file
**GET /api/search/folders** - Tìm folder
**GET /api/search/suggestions** - Gợi ý tìm kiếm

#### Query params

- `q`: Search query
- `page`: Page number
- `per_page`: Items per page

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "results": [],
    "total": 0
  },
  "error": null,
  "meta": null
}
```

