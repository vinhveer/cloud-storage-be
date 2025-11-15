2.3.  API: POST /api/admin/users 
Description: Tạo user mới

### 1) Unauthenticated -> expect 401
POST {{base}}/api/admin/users
Accept: {{json}}
Content-Type: {{json}}

{
  "name": "Test User",
  "email": "test1@example.com",
  "password": "secret123",
  "role": "user"
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:04:01 GMT
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
POST {{base}}/api/admin/users
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{user_token}}

{
  "name": "Test User",
  "email": "testuser1@gmail.com",
  "password": "12345678",
  "role": "user"
}

HTTP/1.1 500 Internal Server Error
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:04:11 GMT
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

### 3) Validation missing -> expect 422
POST {{base}}/api/admin/users
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{admin_token}}

{}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:04:22 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "name": [
      "The name field is required."
    ],
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password field is required."
    ],
    "role": [
      "The role field is required."
    ]
  }
}

### 4) 200
POST {{base}}/api/admin/users
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{admin_token}}

{
  "name": "user 1111",
  "email": "user1111@gmail.com",
  "password": "12345678",
  "role": "user"
}

HTTP/1.1 201 Created
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:04:48 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "user": {
    "user_id": 16,
    "name": "user 1111",
    "email": "user1111@gmail.com",
    "role": "user",
    "storage_limit": 10737418240,
    "storage_used": 0
  }
}