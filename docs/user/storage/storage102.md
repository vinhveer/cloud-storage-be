10.2. API: GET /api/storage/breakdown
Description: Phân tích dung lượng đã dùng của user theo loại file (extension) hoặc loại MIME.

# 1) Unauthenticated request -> expect 401

GET {{base}}/api/storage/breakdown
Accept: {{json}}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 12:52:54 GMT
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

# 2) Authenticated request -> group by extension (default)

GET {{base}}/api/storage/breakdown
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 12:53:07 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "user_id": 15,
  "breakdown": [
    {
      "type": "pdf",
      "total_size": 3411396,
      "count": 4
    }
  ],
  "total_size": 3411396
}

# 3) Authenticated request -> group by MIME

GET {{base}}/api/storage/breakdown?by=mime
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 12:53:24 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "user_id": 15,
  "breakdown": [
    {
      "type": "application\/pdf",
      "total_size": 3411396,
      "count": 4
    }
  ],
  "total_size": 3411396
}