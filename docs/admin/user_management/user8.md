2.8. API: PUT /api/admin/users/{id}/role 
Description: Thay đổi vai trò user

### 1) Unauthenticated -> expect 401
PUT {{base}}/api/admin/users/2/role
Accept: {{json}}
Content-Type: {{json}}

{
  "role": "admin"
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:11:54 GMT
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
PUT {{base}}/api/admin/users/2/role
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{user_token}}

{
  "role": "admin"
}

HTTP/1.1 500 Internal Server Error
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:12:39 GMT
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
PUT {{base}}/api/admin/users/7/role
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{admin_token}}

{}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:12:47 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "role": [
      "The role field is required."
    ]
  }
}

### 4) Invalid role -> expect 422
PUT {{base}}/api/admin/users/2/role
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{admin_token}}

{
  "role": "owner"
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:12:55 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "role": [
      "The selected role is invalid."
    ]
  }
}

### 5) User not found -> expect 404
PUT {{base}}/api/admin/users/999999/role
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{admin_token}}

{
  "role": "admin"
}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:13:06 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "message": "User not found."
}

### 6) Self-demotion -> expect 422
# Using admin_token, attempt to change own role to 'user' (if admin id=1)
PUT {{base}}/api/admin/users/1/role
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{admin_token}}

{
  "role": "user"
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:13:21 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "message": "You cannot change your own role to a lower privilege."
}

### 7) Success -> expect 200 and updated user
PUT {{base}}/api/admin/users/12/role
Accept: {{json}}
Content-Type: {{json}}
Authorization: Bearer {{admin_token}}

{
  "role": "admin"
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 14:13:58 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "message": "Role updated successfully.",
  "user": {
    "user_id": 12,
    "role": "admin"
  }
}