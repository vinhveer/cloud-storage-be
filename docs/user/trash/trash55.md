5.5. API: DELETE /api/trash/empty
Description: Dọn sạch toàn bộ thùng rác của người dùng (xóa vĩnh viễn tất cả file & folder đã bị xóa).

### 1) Happy path — Empty trash (authenticated) — 200 OK
DELETE {{baseUrl}}/api/trash/empty
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:26:23 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Trash emptied successfully.",
    "deleted_count": {
      "files": 1,
      "folders": 2
    }
  },
  "error": null,
  "meta": null
}

### 2) Unauthenticated — no token — 401 Unauthorized
DELETE {{baseUrl}}/api/trash/empty
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:26:41 GMT
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