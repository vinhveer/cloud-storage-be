5.1. API: GET /api/trash
Description: Lấy danh sách file & folder đã bị xóa (trash) của người dùng hiện tại.

### 1) Unauthenticated - should return 401
GET {{base}}/api/trash
Accept: {{json}}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:15:04 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Unauthenticated.",
    "code": "UNAUTHENTICATED",
    "errors": null
  },
  "meta": null
}

### 2) List combined trash (authenticated) - success
GET {{base}}/api/trash
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:15:54 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "items": [
      {
        "id": 29,
        "type": "folder",
        "title": "\u0110\u1ed3 \u00e1n m\u1edbi",
        "deleted_at": "2025-11-15 11:15:50",
        "file_size": null,
        "mime_type": null,
        "file_extension": null,
        "parent_id": null
      },
      {
        "id": 45,
        "type": "file",
        "title": "updated_report.txt",
        "deleted_at": "2025-11-15 09:55:41",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "parent_id": null
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total_pages": 1,
      "total_items": 2
    }
  },
  "error": null,
  "meta": null
}

### 3) Search (files & folders)
GET {{base}}/api/trash?search=u
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:16:28 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "items": [
      {
        "id": 45,
        "type": "file",
        "title": "updated_report.txt",
        "deleted_at": "2025-11-15 09:55:41",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "parent_id": null
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total_pages": 1,
      "total_items": 1
    }
  },
  "error": null,
  "meta": null
}

### 4) Pagination - page 2, 1 item per page
GET {{base}}/api/trash?page=2&per_page=1
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:16:42 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "items": [
      {
        "id": 45,
        "type": "file",
        "title": "updated_report.txt",
        "deleted_at": "2025-11-15 09:55:41",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "parent_id": null
      }
    ],
    "pagination": {
      "current_page": 2,
      "per_page": 1,
      "total_pages": 2,
      "total_items": 2
    }
  },
  "error": null,
  "meta": null
}