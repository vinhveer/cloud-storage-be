4.1. API: POST /api/files/{id}/versions
Description: Upload version mới cho file hiện tại

### 1) Happy path — 201 Created
POST {{baseUrl}}/api/files/51/versions
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: multipart/form-data; boundary=MyBoundary

--MyBoundary
Content-Disposition: form-data; name="action"

update
--MyBoundary
Content-Disposition: form-data; name="notes"

Updated chapter 3
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
Date: Sat, 15 Nov 2025 11:00:58 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "New version uploaded successfully.",
    "version": {
      "version_id": 63,
      "file_id": 51,
      "user_id": 15,
      "version_number": 2,
      "uuid": "b2e17b8b-de58-4466-85aa-225924b3cb6f",
      "file_extension": "pdf",
      "mime_type": "application/pdf",
      "file_size": 852849,
      "action": "update",
      "notes": "Updated chapter 3",
      "created_at": "2025-11-15T11:00:58+00:00"
    }
  },
  "error": null,
  "meta": null
}

### 2) Validation fail — 422 (missing file)
POST {{baseUrl}}/api/files/12/versions
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: multipart/form-data; boundary=MyBoundary

--MyBoundary
Content-Disposition: form-data; name="action"

upload
--MyBoundary--

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:01:29 GMT
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

### 3) Unauthenticated — 401
POST {{baseUrl}}/api/files/12/versions
Accept: application/json
Content-Type: multipart/form-data; boundary=MyBoundary

--MyBoundary
Content-Disposition: form-data; name="action"

upload
--MyBoundary
Content-Disposition: form-data; name="file"; filename="example.pdf"
Content-Type: application/pdf

< ./tests/fixtures/example.pdf
--MyBoundary--

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:01:44 GMT
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

### 4) Forbidden — 403 (no edit permission)
POST {{baseUrl}}/api/files/16/versions
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: multipart/form-data; boundary=MyBoundary

--MyBoundary
Content-Disposition: form-data; name="action"

upload
--MyBoundary
Content-Disposition: form-data; name="file"; filename="example.pdf"
Content-Type: application/pdf

< ./tests/fixtures/example.pdf
--MyBoundary--

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:01:56 GMT
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

### 5) Not found — 404 (file not exist)
POST {{baseUrl}}/api/files/999999/versions
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: multipart/form-data; boundary=MyBoundary

--MyBoundary
Content-Disposition: form-data; name="action"

upload
--MyBoundary
Content-Disposition: form-data; name="file"; filename="example.pdf"
Content-Type: application/pdf

< ./tests/fixtures/example.pdf
--MyBoundary--

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:02:12 GMT
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