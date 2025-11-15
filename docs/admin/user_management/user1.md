2.1. API: GET /api/admin/users
Description: Danh sách tất cả người dùng (có tìm kiếm & phân trang)

### 1) Unauthenticated -> expect 401
GET {{base}}/api/admin/users
Accept: {{json}}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:59:21 GMT
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

### 2) Not admin -> expect 500
GET {{base}}/api/admin/users
Accept: {{json}}
Authorization: Bearer {{user_token}}

HTTP/1.1 500 Internal Server Error
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:59:12 GMT
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


### 3) Admin - basic list
GET {{base}}/api/admin/users
Accept: {{json}}
Authorization: Bearer {{admin_token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:59:28 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "data": [
    {
      "user_id": 1,
      "name": "Pascale Runolfsson",
      "email": "admin@gmail.com",
      "role": "admin",
      "storage_limit": 53687091200,
      "storage_used": 16982758
    },
    {
      "user_id": 2,
      "name": "Kip Rippin",
      "email": "glittel@gmail.com",
      "role": "user",
      "storage_limit": 10737418240,
      "storage_used": 18389629
    },
    {
      "user_id": 3,
      "name": "Dr. Loy Mitchell",
      "email": "prince.crooks@gmail.com",
      "role": "user",
      "storage_limit": 10737418240,
      "storage_used": 28996866
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 11
  }
}

### 4) Search by name/email
GET {{base}}/api/admin/users?search=tr
Accept: {{json}}
Authorization: Bearer {{admin_token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:59:47 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "data": [
    {
      "user_id": 8,
      "name": "tritt",
      "email": "tritt13579@gmail.com",
      "role": "user",
      "storage_limit": 10737418240,
      "storage_used": 0
    },
    {
      "user_id": 15,
      "name": "tritt",
      "email": "tritt666666@gmail.com",
      "role": "user",
      "storage_limit": 10737418240,
      "storage_used": 41854032
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 2
  }
}


### 6) Pagination
GET {{base}}/api/admin/users?page=2&per_page=1
Accept: {{json}}
Authorization: Bearer {{admin_token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:01:06 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "data": [
    {
      "user_id": 2,
      "name": "Kip Rippin",
      "email": "glittel@gmail.com",
      "role": "user",
      "storage_limit": 10737418240,
      "storage_used": 18389629
    }
  ],
  "pagination": {
    "current_page": 2,
    "total_pages": 11,
    "total_items": 11
  }
}