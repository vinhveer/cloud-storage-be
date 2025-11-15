6.3. API: GET /api/folders/{id}
Description: Lấy thông tin chi tiết của một thư mục.

### Get folder details
GET {{baseUrl}}/api/folders/29
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:16:45 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "folder_id": 29,
    "folder_name": "Folder s\u1ebd \u0111\u01b0\u1ee3c move t\u1edbi",
    "fol_folder_id": null,
    "user_id": 15,
    "created_at": "2025-11-15T09:15:08.000000Z",
    "is_deleted": false,
    "deleted_at": null
  },
  "error": null,
  "meta": null
}

### Folder not found/not owned — 404
GET {{baseUrl}}/api/folders/999999999
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:17:23 GMT
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

### Unauthenticated — 401
GET {{baseUrl}}/api/folders/29
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:17:40 GMT
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

### Forbidden — 403
GET {{baseUrl}}/api/folders/3
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:18:12 GMT
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