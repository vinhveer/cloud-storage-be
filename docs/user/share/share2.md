7.2. API: GET /api/shares
Description: Danh sách tất cả các shares mà người dùng hiện tại đã tạo

### 1) Unauthenticated -> expect 401
GET {{base}}/api/shares
Accept: {{json}}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:20:12 GMT
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

### 2) Authenticated - basic list
GET {{base}}/api/shares
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:29:29 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "share_id": 2,
        "shareable_type": "file",
        "shareable_name": "ss.txt",
        "shared_with_count": 2,
        "created_at": "2025-11-15 15:00:15"
      },
      {
        "share_id": 1,
        "shareable_type": "file",
        "shareable_name": "BaoCaoBaiTapNhom_Nhom2_CNPM_64CNTT2.docx",
        "shared_with_count": 3,
        "created_at": "2025-11-15 14:36:10"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 2
    }
  },
  "error": null,
  "meta": null
}

### 4) Page 2
GET {{base}}/api/shares?page=2&per_page=1
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:29:59 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "share_id": 1,
        "shareable_type": "file",
        "shareable_name": "BaoCaoBaiTapNhom_Nhom2_CNPM_64CNTT2.docx",
        "shared_with_count": 3,
        "created_at": "2025-11-15 14:36:10"
      }
    ],
    "pagination": {
      "current_page": 2,
      "total_pages": 2,
      "total_items": 2
    }
  },
  "error": null,
  "meta": null
}

