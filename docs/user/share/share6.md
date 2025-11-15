7.6. API: GET /api/shares/received
Description: Danh sách tất cả shares mà người dùng hiện tại được chia sẻ

### 1) Unauthenticated -> expect 401
GET {{base}}/api/shares/received
Accept: {{json}}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:36:26 GMT
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

### 3) Authenticated - basic list
GET {{base}}/api/shares/received
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:36:51 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "data": [
    {
      "share_id": 3,
      "shareable_type": "file",
      "shareable_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
      "owner": {
        "user_id": 5,
        "name": "Prof. Abigayle Ward DVM"
      },
      "permission": "edit",
      "shared_at": "2025-11-15 15:34:37"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 1
  }
}

### 5) Pagination - Page 1
GET {{base}}/api/shares/received?page=1&per_page=1
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:37:13 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "data": [
    {
      "share_id": 3,
      "shareable_type": "file",
      "shareable_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
      "owner": {
        "user_id": 5,
        "name": "Prof. Abigayle Ward DVM"
      },
      "permission": "edit",
      "shared_at": "2025-11-15 15:34:37"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 1
  }
}