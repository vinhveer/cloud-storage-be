12.1. API: GET /api/dashboard 
Description: Lấy tổng quan trang Dashboard của người dùng hiện tại (số lượng file, folder, dung lượng, v.v).

### Get dashboard overview
# Lấy tổng quan dashboard
GET {{base}}/api/dashboard
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 12:57:20 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "files_count": 4,
    "folders_count": 1,
    "storage_used": 3411396,
    "storage_limit": 10737418240,
    "storage_usage_percent": 0.03,
    "recent_activity_count": 0
  },
  "error": null,
  "meta": null
}