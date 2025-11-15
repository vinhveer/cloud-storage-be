6.12. API: GET /api/folders/{id}/breadcrumb
Description: Lấy đường dẫn breadcrumb từ thư mục gốc đến thư mục hiện tại.

### 1) Happy path — authenticated user
GET {{baseUrl}}/api/folders/33/breadcrumb
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:57:16 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "breadcrumb": [
      {
        "folder_id": 29,
        "folder_name": "\u0110\u1ed3 \u00e1n m\u1edbi"
      },
      {
        "folder_id": 33,
        "folder_name": "B\u00e0i t\u1eadp 11"
      }
    ]
  },
  "error": null,
  "meta": null
}

### 2) Unauthenticated — no token
GET {{baseUrl}}/api/folders/5/breadcrumb
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:57:44 GMT
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

### 3) Not found (invalid id or no access)
GET {{baseUrl}}/api/folders/999999/breadcrumb
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:57:55 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Folder not found",
    "code": "FOLDER_NOT_FOUND",
    "errors": null
  },
  "meta": null
}


### 4) forbidden — access folder not owned by user
GET {{baseUrl}}/api/folders/1/breadcrumb
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:58:39 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Folder not accessible",
    "code": "FORBIDDEN",
    "errors": null
  },
  "meta": null
}