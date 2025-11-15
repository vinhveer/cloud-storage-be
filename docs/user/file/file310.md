3.10. API: POST /api/files/{id}/move
Description: Di chuyển file sang thư mục khác

### 1) Happy path — 200 OK
POST {{baseUrl}}/api/files/47/move
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "destination_folder_id": 29
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:03:40 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "success": true,
    "message": "File moved successfully.",
    "file": {
      "file_id": 47,
      "folder_id": 29
    }
  },
  "error": null,
  "meta": null
}

### 2)  Happy path — 200 OK (missing destination_folder_id - stays in root)
POST {{baseUrl}}/api/files/47/move
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:04:04 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "success": true,
    "message": "File moved successfully.",
    "file": {
      "file_id": 47,
      "folder_id": null
    }
  },
  "error": null,
  "meta": null
}

### 3) Destination folder not found — 404
POST {{baseUrl}}/api/files/19/move
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "destination_folder_id": 999999
}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:04:41 GMT
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

### 4) Unauthenticated — 401
POST {{baseUrl}}/api/files/4/move
Accept: application/json
Content-Type: application/json

{
    "destination_folder_id": 20
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:04:50 GMT
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

### 5) Not found (file) — 404
POST {{baseUrl}}/api/files/999999/move
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "destination_folder_id": 3
}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:05:14 GMT
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

### 6) Forbidden (other user's file) — 403
# Use a token that belongs to a different user than the file owner
POST {{baseUrl}}/api/files/4/move
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "destination_folder_id": 3
}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:05:25 GMT
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