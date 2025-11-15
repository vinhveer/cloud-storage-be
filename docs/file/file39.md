3.9. API: POST /api/files/{id}/copy
Description: Sao chép file sang vị trí khác

### 1) Happy path — copy ALL versions (default) — 200 OK
POST {{baseUrl}}/api/files/46/copy
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "destination_folder_id": null
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:58:44 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "success": true,
    "message": "File copied successfully.",
    "new_file": {
      "file_id": 47,
      "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
      "folder_id": null
    }
  },
  "error": null,
  "meta": null
}

### 2) Happy path — copy ONLY latest version via query — 200 OK
POST {{baseUrl}}/api/files/46/copy?only_latest=true
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "destination_folder_id": null
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:59:43 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "success": true,
    "message": "File copied successfully.",
    "new_file": {
      "file_id": 48,
      "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy.pdf",
      "folder_id": null
    }
  },
  "error": null,
  "meta": null
}

### 3) Happy path — copy ONLY latest version via JSON body — 200 OK
POST {{baseUrl}}/api/files/46/copy
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "destination_folder_id": null,
  "only_latest": true
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:00:05 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "success": true,
    "message": "File copied successfully.",
    "new_file": {
      "file_id": 49,
      "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_2.pdf",
      "folder_id": null
    }
  },
  "error": null,
  "meta": null
}

### 5) Unauthenticated — 401
POST {{baseUrl}}/api/files/4/copy
Accept: application/json
Content-Type: application/json

{
  "destination_folder_id": 20
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:00:55 GMT
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

### 6) Not found — 404 (file not found)
POST {{baseUrl}}/api/files/999999/copy
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "destination_folder_id": 2
}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:01:06 GMT
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

### 7) Forbidden — 403 (other user's file)
# Use a token that belongs to a different user than the file owner
POST {{baseUrl}}/api/files/35/copy
Accept: application/json
Authorization: Bearer {{otherToken}}
Content-Type: application/json

{
  "destination_folder_id": 1
}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:01:29 GMT
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