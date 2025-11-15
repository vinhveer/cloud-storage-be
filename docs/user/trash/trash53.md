5.3. API: POST /api/trash/{id}/restore 
Description: Khôi phục 1 file hoặc folder khỏi thùng rác

### 1) Happy path - restore top-level file (200)
POST {{baseUrl}}/api/trash/45/restore
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "type": "file"
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:19:20 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Item restored successfully.",
    "restored_item": {
      "id": 45,
      "type": "file",
      "display_name": "updated_report.txt"
    }
  },
  "error": null,
  "meta": null
}

### 2) Happy path - restore top-level folder (200)
POST {{baseUrl}}/api/trash/29/restore
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "type": "folder"
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:19:37 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Item restored successfully.",
    "restored_item": {
      "id": 29,
      "type": "folder",
      "display_name": "\u0110\u1ed3 \u00e1n m\u1edbi"
    }
  },
  "error": null,
  "meta": null
}


### 3) Validation fail - missing type (500)
POST {{baseUrl}}/api/trash/15/restore
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
}

HTTP/1.1 500 Internal Server Error
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:19:52 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "The type field is required.",
    "code": "INTERNAL_ERROR",
    "errors": null
  },
  "meta": {
    "exception": "Illuminate\\Validation\\ValidationException",
    "file": "\/var\/www\/html\/cloud-storage-be\/vendor\/laravel\/framework\/src\/Illuminate\/Foundation\/Http\/FormRequest.php",
    "line": 168
  }
}

### 4) Validation fail - invalid type (500)
POST {{baseUrl}}/api/trash/15/restore
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "type": "unknown"
}

HTTP/1.1 500 Internal Server Error
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:20:12 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "The selected type is invalid.",
    "code": "INTERNAL_ERROR",
    "errors": null
  },
  "meta": {
    "exception": "Illuminate\\Validation\\ValidationException",
    "file": "\/var\/www\/html\/cloud-storage-be\/vendor\/laravel\/framework\/src\/Illuminate\/Foundation\/Http\/FormRequest.php",
    "line": 168
  }
}

### 5) Unauthenticated - no token (401)
POST {{baseUrl}}/api/trash/15/restore
Accept: application/json
Content-Type: application/json

{
  "type": "file"
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:20:28 GMT
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

### 6) Bad request - trying to restore a child item directly (400)
# Replace 16 with an actual child trashed file id to test
POST {{baseUrl}}/api/trash/46/restore
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "type": "file"
}

HTTP/1.1 400 Bad Request
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:21:48 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Cannot restore child item directly. Please restore the top-level parent.",
    "code": null,
    "errors": null
  },
  "meta": null
}

### 7) Not found / Not in trash (400)
# Replace 99999 with an id that does not exist or is not trashed
POST {{baseUrl}}/api/trash/99999/restore
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "type": "file"
}


HTTP/1.1 400 Bad Request
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:21:59 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "File not found in trash",
    "code": null,
    "errors": null
  },
  "meta": null
}