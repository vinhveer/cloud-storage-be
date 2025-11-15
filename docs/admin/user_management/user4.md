2.4. API: PUT /api/admin/users/{id} 
Description: Cập nhật thông tin user (name & storage_limit)

### 1) Unauthenticated -> expect 401
PUT {{base}}/api/admin/users/2
Accept: {{json}}
Content-Type: {{json}}

{
  "name": "Updated Name"
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:06:34 GMT
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
PUT {{base}}/api/admin/users/2
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{user_token}}

{
  "name": "Updated Name"
}

HTTP/1.1 500 Internal Server Error
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:06:43 GMT
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

### 5) User not found -> expect 404
PUT {{base}}/api/admin/users/999999
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{admin_token}}

{
  "name": "Updated Name"
}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:06:52 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "message": "User not found."
}

### 6) Success - update name + storage_limit
PUT {{base}}/api/admin/users/12
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{admin_token}}

{
  "name": "Updated Name",
  "storage_limit": 1000000
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:07:05 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "user": {
    "user_id": 12,
    "name": "Updated Name",
    "email": "testuser3@gmail.com",
    "role": "user",
    "storage_limit": 1000000,
    "storage_used": 0
  }
}