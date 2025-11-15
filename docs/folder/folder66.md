6.6. API: DELETE /api/folders/{id}
Description: Xóa mềm (soft delete) folder — folder sẽ chuyển vào Trash (bao gồm tất cả file và folder con).

### Delete parent folder as owner (expect 200)
DELETE {{baseUrl}}/api/folders/29
Accept: application/json
Authorization: Bearer {{tokenA}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:41:47 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Folder moved to trash."
  },
  "error": null,
  "meta": null
}

### Not found / no access — 404
DELETE {{baseUrl}}/api/folders/999999
Accept: application/json
Authorization: Bearer {{tokenA}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:43:00 GMT
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
DELETE {{baseUrl}}/api/folders/{{parentId}}
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:43:20 GMT
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

### Forbidden — 403
DELETE {{baseUrl}}/api/folders/1
Accept: application/json
Authorization: Bearer {{tokenA}}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:43:43 GMT
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