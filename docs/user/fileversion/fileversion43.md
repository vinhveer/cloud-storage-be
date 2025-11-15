4.3. API: GET /api/files/{id}/versions/{version_id}
Description: Lấy chi tiết metadata của version cụ thể

### 1) Happy path — 200 OK
GET {{baseUrl}}/api/files/51/versions/63
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:05:18 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "version_id": 63,
    "file_id": 51,
    "version_number": 2,
    "uuid": "b2e17b8b-de58-4466-85aa-225924b3cb6f",
    "file_extension": "pdf",
    "mime_type": "application/pdf",
    "file_size": 852849,
    "action": "update",
    "notes": "Updated chapter 3",
    "created_at": "2025-11-15T11:00:58+00:00",
    "uploaded_by": {
      "user_id": 15,
      "name": "tritt"
    }
  },
  "error": null,
  "meta": null
}

### 2) Unauthenticated — 401
GET {{baseUrl}}/api/files/12/versions/3
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:05:34 GMT
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


### 3) Forbidden / No permission — 403
# Use a token for a user who doesn't have access to file 12
GET {{baseUrl}}/api/files/16/versions/16
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:05:46 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "File not owned by user",
    "code": "FORBIDDEN",
    "errors": null
  },
  "meta": null
}

### 4) File not found — 404
GET {{baseUrl}}/api/files/999999/versions/3
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:05:56 GMT
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

### 5) Version not found — 404
GET {{baseUrl}}/api/files/51/versions/999999
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:06:12 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "File version not found",
    "code": "FILE_VERSION_NOT_FOUND",
    "errors": null
  },
  "meta": null
}