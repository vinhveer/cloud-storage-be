4.5. API: POST /api/files/{id}/versions/{version_id}/restore
Description: Khôi phục version cụ thể thành version hiện tại của file

### 1) Happy path — 201 Created
POST {{baseUrl}}/api/files/51/versions/62/restore
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 201 Created
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:09:17 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Version restored successfully.",
    "restored_to_version": {
      "version_id": 64,
      "version_number": 3,
      "action": "restore",
      "restored_at": "2025-11-15T11:09:17+00:00"
    }
  },
  "error": null,
  "meta": null
}

### 2) Unauthenticated — 401
POST {{baseUrl}}/api/files/123/versions/3/restore
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:09:39 GMT
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

### 3) Forbidden — 403 (no edit permission)
POST {{baseUrl}}/api/files/16/versions/29/restore
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:09:49 GMT
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

### 4) File not found — 404
POST {{baseUrl}}/api/files/999999/versions/3/restore
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:09:59 GMT
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


### 5) Version not found — 404
POST {{baseUrl}}/api/files/51/versions/999999/restore
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:10:15 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "File version not found",
    "code": "FILE_VERSION_NOT_FOUND",
    "errors": null
  },
  "meta": null
}