10.3. API: GET /api/storage/limit
Description: Lấy giới hạn dung lượng (quota) và dung lượng còn trống của người dùng hiện tại.

# 1) Unauthenticated -> expect 401

GET {{base}}/api/storage/limit
Accept: {{json}}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 12:54:10 GMT
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

GET {{base}}/api/storage/limit
Accept: {{json}}

#################################################################
# 2) Authenticated -> user has `storage_limit` set
#################################################################

GET {{base}}/api/storage/limit
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 12:54:29 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "user_id": 15,
  "storage_limit": 10737418240,
  "storage_used": 3411396,
  "remaining": 10734006844,
  "formatted": {
    "limit": "10 GB",
    "used": "3.25 MB",
    "remaining": "10 GB"
  }
}