3.5. API: PUT /api/files/{id}
Description: Cập nhật thông tin file (đổi tên)

### 1) Happy path — 200 OK
PUT {{baseUrl}}/api/files/45
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "display_name": "updated_report.txt"
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:53:39 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "File updated successfully.",
    "file": {
      "file_id": 45,
      "display_name": "updated_report.txt",
      "folder_id": null
    }
  },
  "error": null,
  "meta": null
}

### 2) Validation fail — 422 (missing both fields)
PUT {{baseUrl}}/api/files/4
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:53:53 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Validation failed",
    "code": "VALIDATION_ERROR",
    "errors": {
      "payload": [
        "At least one of display_name or folder_id must be provided"
      ]
    }
  },
  "meta": null
}

### 3) Validation fail — 422 (invalid folder_id)
PUT {{baseUrl}}/api/files/4
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "folder_id": -5
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:54:08 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Validation failed",
    "code": "VALIDATION_ERROR",
    "errors": {
      "folder_id": [
        "The folder id field must be at least 1."
      ]
    }
  },
  "meta": null
}

### 4) Unauthenticated — 401
PUT {{baseUrl}}/api/files/4
Accept: application/json
Content-Type: application/json

{
  "display_name": "should_fail.pdf"
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:54:29 GMT
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

### 5) Not found — 404 (file not found)
PUT {{baseUrl}}/api/files/999999
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "display_name": "nope.pdf"
}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:54:40 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "File not found",
    "code": "FILE_NOT_FOUND",
    "errors": null
  },
  "meta": null
}

### 6) Forbidden (other user's file) — 403
# Use a token that belongs to a different user than the file owner
PUT {{baseUrl}}/api/files/18
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "display_name": "forbidden_change.pdf"
}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:54:52 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "File not accessible",
    "code": "FORBIDDEN",
    "errors": null
  },
  "meta": null
}