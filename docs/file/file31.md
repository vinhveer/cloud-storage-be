3.1. API: POST /api/files XONG
Description: Upload file(s) mới vào hệ thống

### 1) Upload file - success (root folder) — expects 201 Created
POST {{baseUrl}}/api/files
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: multipart/form-data; boundary=MyBoundary

--MyBoundary
Content-Disposition: form-data; name="file"; filename="BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf"
Content-Type: application/pdf

< ./BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf
--MyBoundary--

HTTP/1.1 201 Created
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:13:43 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "File uploaded successfully.",
    "file": {
      "file_id": 45,
      "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
      "file_size": 852849,
      "mime_type": "application/pdf",
      "file_extension": "pdf",
      "folder_id": null,
      "user_id": 15,
      "created_at": "2025-11-15T09:13:43.000000Z"
    }
  },
  "error": null,
  "meta": null
}

### 2) Upload file - success (with folder_id) — expects 201 Created
POST {{baseUrl}}/api/files
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: multipart/form-data; boundary=MyBoundary

--MyBoundary
Content-Disposition: form-data; name="file"; filename="BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf"
Content-Type: application/pdf

< ./BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf
--MyBoundary
Content-Disposition: form-data; name="folder_id"

29
--MyBoundary
Content-Disposition: form-data; name="BaoCaoTTCS_TranThanhTri_64132989_64CNTT3"

Project Document.pdf
--MyBoundary--

HTTP/1.1 201 Created
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:16:14 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "File uploaded successfully.",
    "file": {
      "file_id": 46,
      "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
      "file_size": 852849,
      "mime_type": "application/pdf",
      "file_extension": "pdf",
      "folder_id": 29,
      "user_id": 15,
      "created_at": "2025-11-15T09:16:14.000000Z"
    }
  },
  "error": null,
  "meta": null
}

### 3) Validation error - missing file (422)
POST {{baseUrl}}/api/files
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: multipart/form-data; boundary=MyBoundary

--MyBoundary
Content-Disposition: form-data; name="BaoCaoTTCS_TranThanhTri_64132989_64CNTT3"

No file provided
--MyBoundary--

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:16:54 GMT
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
      "file": [
        "The file field is required."
      ]
    }
  },
  "meta": null
}

### 5) Domain error - folder not found or not owned (404)
POST {{baseUrl}}/api/files
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: multipart/form-data; boundary=MyBoundary

--MyBoundary
Content-Disposition: form-data; name="file"; filename="hello.txt"
Content-Type: text/plain

Hello world
--MyBoundary
Content-Disposition: form-data; name="folder_id"

999999
--MyBoundary--

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:17:14 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Parent folder not found or not owned by user",
    "code": "FOLDER_NOT_FOUND",
    "errors": null
  },
  "meta": null
}

### 6) Domain error - storage limit exceeded (409)
# Precondition: user's storage_limit or system default_storage_limit is set low, or storage_used close to limit.
POST {{baseUrl}}/api/files
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: multipart/form-data; boundary=MyBoundary


--MyBoundary
Content-Disposition: form-data; name="file"; filename="BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf"
Content-Type: application/pdf

< ./BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf
--MyBoundary
Content-Disposition: form-data; name="folder_id"

29
--MyBoundary
Content-Disposition: form-data; name="BaoCaoTTCS_TranThanhTri_64132989_64CNTT3"

Project Document.pdf
--MyBoundary--

HTTP/1.1 409 Conflict
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:18:19 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Storage limit exceeded",
    "code": "STORAGE_LIMIT_EXCEEDED",
    "errors": null
  },
  "meta": null
}

### 7) Unauthenticated — 401
POST {{baseUrl}}/api/files
Accept: application/json
Content-Type: multipart/form-data; boundary=MyBoundary

--MyBoundary
Content-Disposition: form-data; name="file"; filename="hello.txt"
Content-Type: text/plain

Hello world without token
--MyBoundary--

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:18:49 GMT
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