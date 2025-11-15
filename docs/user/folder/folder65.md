6.5. API: PUT /api/folders/{id}
Description: Đổi tên folder.

### Rename folder successfully (200)
PUT {{baseUrl}}/api/folders/{{folderId}}
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "folder_name": "Đồ án mới"
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:39:22 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Folder renamed successfully.",
    "folder": {
      "folder_id": 29,
      "folder_name": "\u0110\u1ed3 \u00e1n m\u1edbi"
    }
  },
  "error": null,
  "meta": null
}

### Missing folder_name (422)
PUT {{baseUrl}}/api/folders/{{folderId}}
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{token}}

{
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:39:44 GMT
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
      "folder_name": [
        "The folder name field is required."
      ]
    }
  },
  "meta": null
}

### Folder not found / not owned (404)
PUT {{baseUrl}}/api/folders/999999
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "folder_name": "New name"
}


HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:39:56 GMT
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


### Unauthenticated — 401
PUT {{baseUrl}}/api/folders/{{folderId}}
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:40:14 GMT
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