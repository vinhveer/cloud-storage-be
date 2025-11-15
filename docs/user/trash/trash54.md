5.4. API: DELETE /api/trash/{id}
Description: Xóa vĩnh viễn 1 file hoặc folder khỏi hệ thống (không thể khôi phục).


### 1) Happy path - delete trashed file (200)
DELETE {{baseUrl}}/api/trash/45
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
Date: Sat, 15 Nov 2025 11:23:12 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Item permanently deleted."
  },
  "error": null,
  "meta": null
}

### 2) Happy path - delete trashed folder (200)
DELETE {{baseUrl}}/api/trash/29
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
Date: Sat, 15 Nov 2025 11:23:41 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Item permanently deleted."
  },
  "error": null,
  "meta": null
}

### 3) Validation fail - missing/invalid type (422)
DELETE {{baseUrl}}/api/trash/1
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
	"type": "invalid"
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:23:53 GMT
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
      "type": [
        "The selected type is invalid."
      ]
    }
  },
  "meta": null
}

### 4) Unauthenticated (401)
DELETE {{baseUrl}}/api/trash/1
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
Date: Sat, 15 Nov 2025 11:24:02 GMT
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

### 5) Cannot delete child item directly (400)
DELETE {{baseUrl}}/api/trash/51
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
Date: Sat, 15 Nov 2025 11:24:59 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Failed to delete item: Cannot delete child item directly. Please delete the top-level parent.",
    "code": "DELETE_FAILED",
    "errors": null
  },
  "meta": null
}

### 6) Not found in trash (400)
DELETE {{baseUrl}}/api/trash/999999
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
Date: Sat, 15 Nov 2025 11:25:09 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Failed to delete item: File not found in trash",
    "code": "DELETE_FAILED",
    "errors": null
  },
  "meta": null
}
