3.3. API: GET /api/files/{id} 
Description: Lấy chi tiết thông tin file (metadata)

### 1) Happy path — 200 OK
GET {{baseUrl}}/api/files/46
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:46:20 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "file_id": 46,
    "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
    "file_size": 852849,
    "mime_type": "application/pdf",
    "file_extension": "pdf",
    "folder_id": 29,
    "user_id": 15,
    "is_deleted": false,
    "created_at": "2025-11-15T09:16:14+00:00",
    "last_opened_at": null
  },
  "error": null,
  "meta": null
}

### 2) Unauthenticated — 401
GET {{baseUrl}}/api/files/101
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:46:33 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Unauthenticated",
    "code": "UNAUTHENTICATED",
    "errors": null
  },
  "meta": null
}

### 3) Not found — 404
GET {{baseUrl}}/api/files/999999
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:46:42 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "File not found",
    "code": "FILE_NOT_FOUND",
    "errors": null
  },
  "meta": null
}

### 4) Forbidden (other user's file) — 403
# Use a token that belongs to a different user than the file owner
GET {{baseUrl}}/api/files/18
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:46:53 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "File not accessible",
    "code": "FORBIDDEN",
    "errors": null
  },
  "meta": null
}