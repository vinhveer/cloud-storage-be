10.4. API: GET /api/admin/storage/overview
Description: Thống kê tổng quan dung lượng toàn hệ thống: tổng số user, tổng file, dung lượng đã dùng, dung lượng tối đa có thể cấp.

#################################################################
# 1) Unauthenticated -> expect 401
#################################################################

GET {{base}}/api/admin/storage/overview
Accept: {{json}}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:51:46 GMT
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

# 2) Authenticated non-admin -> expect 500

GET {{base}}/api/admin/storage/overview
Accept: {{json}}
Authorization: Bearer {{user_token}}

HTTP/1.1 500 Internal Server Error
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:52:03 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "This action is unauthorized.",
    "code": "INTERNAL_ERROR",
    "errors": null
  },
  "meta": {
    "exception": "Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException",
    "file": "\/var\/www\/html\/cloud-storage-be\/vendor\/laravel\/framework\/src\/Illuminate\/Foundation\/Exceptions\/Handler.php",
    "line": 673
  }
}

#################################################################
# 3) Authenticated admin -> success
#################################################################

GET {{base}}/api/admin/storage/overview
Accept: {{json}}
Authorization: Bearer {{admin_token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:52:19 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "system_overview": {
      "total_users": 11,
      "total_files": 39,
      "total_storage_used": 132054111,
      "total_storage_limit": 171798691840,
      "formatted": {
        "used": "125.94 MB",
        "limit": "160 GB"
      }
    }
  },
  "error": null,
  "meta": null
}