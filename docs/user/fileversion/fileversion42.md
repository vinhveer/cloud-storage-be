4.2. API: GET /api/files/{id}/versions
Description: Danh sách tất cả version của file

### 1) Happy path — 200 OK
GET {{baseUrl}}/api/files/51/versions?page=1&per_page=20
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:03:30 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "version_id": 63,
        "version_number": 2,
        "action": "update",
        "notes": "Updated chapter 3",
        "file_size": 852849,
        "created_at": "2025-11-15T11:00:58+00:00"
      },
      {
        "version_id": 62,
        "version_number": 1,
        "action": "upload",
        "notes": "Copied from file 46 version 1",
        "file_size": 852849,
        "created_at": "2025-11-15T10:45:57+00:00"
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

### 2) Unauthenticated — 401
GET {{baseUrl}}/api/files/1/versions
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:03:42 GMT
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

### 3) File not found — 404
GET {{baseUrl}}/api/files/999999/versions
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:03:53 GMT
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

### 4) Forbidden (not accessible) — 403
GET {{baseUrl}}/api/files/2/versions
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:04:02 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "File not owned by user",
    "code": "FORBIDDEN",
    "errors": null
  },
  "meta": null
}