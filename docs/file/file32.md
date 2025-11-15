3.2. API: GET /api/files XONG
Description: Lấy danh sách files (lọc theo thư mục, tên, loại file, extension)

### 1) Happy path — list my files (default pagination)
GET {{baseUrl}}/api/files
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:43:29 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "file_id": 46,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "folder_id": 29,
        "user_id": 15,
        "is_deleted": false
      },
      {
        "file_id": 45,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "folder_id": null,
        "user_id": 15,
        "is_deleted": false
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

### 2) Filter by folder_id
GET {{baseUrl}}/api/files?folder_id={{folderId}}
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:43:38 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "file_id": 46,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "folder_id": 29,
        "user_id": 15,
        "is_deleted": false
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 1
    }
  },
  "error": null,
  "meta": null
}

### 3) Search by name (keyword)
GET {{baseUrl}}/api/files?search=b
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:43:56 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "file_id": 46,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "folder_id": 29,
        "user_id": 15,
        "is_deleted": false
      },
      {
        "file_id": 45,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "folder_id": null,
        "user_id": 15,
        "is_deleted": false
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

### 4) Filter by extension (pdf)
GET {{baseUrl}}/api/files?extension=pdf
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:44:16 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "file_id": 46,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "folder_id": 29,
        "user_id": 15,
        "is_deleted": false
      },
      {
        "file_id": 45,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "folder_id": null,
        "user_id": 15,
        "is_deleted": false
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

### 5) Combined filters + pagination
GET {{baseUrl}}/api/files?folder_id={{folderId}}&search=b&extension=pdf&page=1&per_page=10
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:44:36 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "file_id": 46,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "folder_id": 29,
        "user_id": 15,
        "is_deleted": false
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 1
    }
  },
  "error": null,
  "meta": null
}

### 6) Validation fail — per_page out of range (422)
GET {{baseUrl}}/api/files?per_page=0
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:44:46 GMT
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
      "per_page": [
        "The per page field must be at least 1."
      ]
    }
  },
  "meta": null
}

### 7) Folder not found/not owned — 404
GET {{baseUrl}}/api/files?folder_id=999999999
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:44:58 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Folder not found or not owned by user",
    "code": "FOLDER_NOT_FOUND",
    "errors": null
  },
  "meta": null
}

### 8) Unauthenticated — 401
GET {{baseUrl}}/api/files
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:45:12 GMT
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