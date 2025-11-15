3.4. API: GET /api/files/{id}/download
Description: Tải xuống file

### 1) Happy path — 200 (binary download)
GET {{baseUrl}}/api/files/46/download
Accept: application/octet-stream
Authorization: Bearer {{token}}

// tự chạy nhé, nó ra binary

### 2) Unauthenticated — 401
GET {{baseUrl}}/api/files/46/download
Accept: application/octet-stream

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:48:38 GMT
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

### 3) File not found — 404
GET {{baseUrl}}/api/files/999999/download
Accept: application/octet-stream
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:48:48 GMT
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

### 4) Forbidden / Not owned — 403
GET {{baseUrl}}/api/files/18/download
Accept: application/octet-stream
Authorization: Bearer {{token}}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:48:58 GMT
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