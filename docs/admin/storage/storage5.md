10.5. API: GET /api/admin/storage/users (Admin only) 
Description: Lấy danh sách dung lượng sử dụng theo từng user, có hỗ trợ tìm kiếm & phân trang.

#################################################################
# 1) Unauthenticated -> expect 401
#################################################################

GET {{base}}/api/admin/storage/users
Accept: {{json}}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:53:38 GMT
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

#################################################################
# 2) Authenticated non-admin -> expect 500
#################################################################

GET {{base}}/api/admin/storage/users
Accept: {{json}}
Authorization: Bearer {{user_token}}

HTTP/1.1 500 Internal Server Error
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:53:52 GMT
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
# 3) Authenticated admin -> default pagination (page=1, per_page=15)
#################################################################

GET {{base}}/api/admin/storage/users
Accept: {{json}}
Authorization: Bearer {{admin_token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:54:02 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "user_id": 1,
        "name": "Pascale Runolfsson",
        "email": "admin@gmail.com",
        "role": "admin",
        "storage_limit": 53687091200,
        "storage_used": 16982758,
        "usage_percent": 0.03
      },
      {
        "user_id": 2,
        "name": "Kip Rippin",
        "email": "glittel@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 18389629,
        "usage_percent": 0.17
      },
      {
        "user_id": 3,
        "name": "Dr. Loy Mitchell",
        "email": "prince.crooks@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 28996866,
        "usage_percent": 0.27
      },
      {
        "user_id": 4,
        "name": "Stefan Gutkowski",
        "email": "alice.goldner@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 8449704,
        "usage_percent": 0.08
      },
      {
        "user_id": 5,
        "name": "Prof. Abigayle Ward DVM",
        "email": "stoltenberg.cicero@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 5491727,
        "usage_percent": 0.05
      },
      {
        "user_id": 6,
        "name": "Jaquelin Larson",
        "email": "howe.myrna@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 11889395,
        "usage_percent": 0.11
      },
      {
        "user_id": 7,
        "name": "tandd",
        "email": "tandd@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 0,
        "usage_percent": 0
      },
      {
        "user_id": 8,
        "name": "tritt",
        "email": "tritt13579@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 0,
        "usage_percent": 0
      },
      {
        "user_id": 12,
        "name": "Updated Name 111",
        "email": "testuser3@gmail.com",
        "role": "user",
        "storage_limit": 21474836480,
        "storage_used": 0,
        "usage_percent": 0
      },
      {
        "user_id": 14,
        "name": "admin 2",
        "email": "admin2@gmail.com",
        "role": "admin",
        "storage_limit": 10737418240,
        "storage_used": 0,
        "usage_percent": 0
      },
      {
        "user_id": 15,
        "name": "tritt",
        "email": "tritt666666@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 41854032,
        "usage_percent": 0.39
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 11
    }
  },
  "error": null,
  "meta": null
}

#################################################################
# 4) Search by name or email
#################################################################

GET {{base}}/api/admin/storage/users?search=tr
Accept: {{json}}
Authorization: Bearer {{admin_token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:54:21 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "user_id": 8,
        "name": "tritt",
        "email": "tritt13579@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 0,
        "usage_percent": 0
      },
      {
        "user_id": 15,
        "name": "tritt",
        "email": "tritt666666@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 41854032,
        "usage_percent": 0.39
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 2
    }
  },
  "error": null,
  "meta": null
}

#################################################################
# 5) Pagination parameters
#################################################################

GET {{base}}/api/admin/storage/users?page=2&per_page=2
Accept: {{json}}
Authorization: Bearer {{admin_token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:54:41 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "user_id": 3,
        "name": "Dr. Loy Mitchell",
        "email": "prince.crooks@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 28996866,
        "usage_percent": 0.27
      },
      {
        "user_id": 4,
        "name": "Stefan Gutkowski",
        "email": "alice.goldner@gmail.com",
        "role": "user",
        "storage_limit": 10737418240,
        "storage_used": 8449704,
        "usage_percent": 0.08
      }
    ],
    "pagination": {
      "current_page": 2,
      "total_pages": 6,
      "total_items": 11
    }
  },
  "error": null,
  "meta": null
}