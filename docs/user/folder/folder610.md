6.10. API: POST /api/folders/{id}/move 
Description: Di chuyển folder sang thư mục đích (cập nhật fol_folder_id).

### 1) Happy path — 200 OK
POST {{baseUrl}}/api/folders/29/move
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
Date: Sat, 15 Nov 2025 10:51:50 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Folder moved successfully."
  },
  "error": null,
  "meta": null
}

### 3) Unauthenticated — 401
POST {{baseUrl}}/api/folders/123/move
Accept: application/json
Content-Type: application/json

{
  "target_folder_id": 45
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:52:32 GMT
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

### 4) Invalid target (not owned)422
POST {{baseUrl}}/api/folders/11/move
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "target_folder_id": 1111
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:52:48 GMT
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
        "The selected target folder id is invalid."
      ]
    }
  },
  "meta": null
}

### 5) Invalid target (not found) — 404
POST {{baseUrl}}/api/folders/11/move
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "target_folder_id": 29
}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:53:11 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Folder not found",
    "code": "FOLDER_NOT_FOUND",
    "errors": null
  },
  "meta": null
}

### 6) Move into descendant — 400 (should be blocked)
POST {{baseUrl}}/api/folders/29/move
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "target_folder_id": 30
}

HTTP/1.1 400 Bad Request
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:54:29 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Cannot move a folder into its own descendant",
    "code": "MOVE_FAILED",
    "errors": null
  },
  "meta": null
}
