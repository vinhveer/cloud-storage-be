6.1. API: POST /api/folders
Description: Tạo folder mới trong thư mục cha (hoặc gốc nếu không có parent_folder_id).

### Create folder at root (200)
POST {{baseUrl}}/api/folders
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "folder_name": "Folder sẽ được move tới"
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 09:15:08 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Folder created successfully.",
    "folder": {
      "folder_id": 29,
      "folder_name": "Folder s\u1ebd \u0111\u01b0\u1ee3c move t\u1edbi",
      "fol_folder_id": null,
      "user_id": 15,
      "created_at": "2025-11-15T09:15:08.000000Z"
    }
  },
  "error": null,
  "meta": null
}

### Create folder inside parent (200)
POST {{baseUrl}}/api/folders
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "folder_name": "Bài tập",
  "parent_folder_id": 29
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:10:02 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Folder created successfully.",
    "folder": {
      "folder_id": 30,
      "folder_name": "B\u00e0i t\u1eadp",
      "fol_folder_id": 29,
      "user_id": 15,
      "created_at": "2025-11-15T10:10:02.000000Z"
    }
  },
  "error": null,
  "meta": null
}

### Missing folder_name (422)
POST {{baseUrl}}/api/folders
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "parent_folder_id": 1
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:10:20 GMT
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

### Invalid/non-owned parent (500)
POST {{baseUrl}}/api/folders
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "folder_name": "Tài liệu học kỳ 2",
  "parent_folder_id": 1
}

HTTP/1.1 500 Internal Server Error
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:11:07 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Parent folder not found or not owned by user",
    "code": "INTERNAL_ERROR",
    "errors": null
  },
  "meta": {
    "exception": "App\\Exceptions\\DomainValidationException",
    "file": "\/var\/www\/html\/cloud-storage-be\/app\/Services\/FolderService.php",
    "line": 32
  }
}


### Unauthenticated — 401
POST {{baseUrl}}/api/folders
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:11:34 GMT
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