6.9. API: POST /api/folders/{id}/copy
Description: Sao chép folder (bao gồm cấu trúc con) sang thư mục đích.

### 1) Happy path — 200 OK
POST {{baseUrl}}/api/folders/29/copy
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "target_folder_id": null
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:45:58 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Folder copied successfully.",
    "new_folder_id": 31
  },
  "error": null,
  "meta": null
}

### 3) Validation fail — 422
POST {{baseUrl}}/api/folders/1/copy
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "target_folder_id": "not-an-int"
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:46:33 GMT
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
      "target_folder_id": [
        "The target folder id field must be an integer."
      ]
    }
  },
  "meta": null
}

### 4) Unauthenticated — 401
POST {{baseUrl}}/api/folders/1/copy
Accept: application/json
Content-Type: application/json

{
  "target_folder_id": 2
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:46:44 GMT
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


### Forbitden — 403
POST {{baseUrl}}/api/folders/1/copy
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "target_folder_id": 2
}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:47:21 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Folder not accessible",
    "code": "FORBIDDEN",
    "errors": null
  },
  "meta": null
}