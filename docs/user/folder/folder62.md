6.2. API: GET /api/folders
Description: Lấy danh sách folder con của parent_id (hoặc thư mục gốc nếu không truyền).

### List root folders (200)
GET {{baseUrl}}/api/folders
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:12:45 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "folder_id": 29,
        "folder_name": "Folder s\u1ebd \u0111\u01b0\u1ee3c move t\u1edbi",
        "fol_folder_id": null,
        "created_at": "2025-11-15T09:15:08.000000Z"
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

### List children of a parent (200)
GET {{baseUrl}}/api/folders?parent_id={{parentId}}
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:13:11 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "folder_id": 30,
        "folder_name": "B\u00e0i t\u1eadp",
        "fol_folder_id": 29,
        "created_at": "2025-11-15T10:10:02.000000Z"
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

### Pagination (200)
GET {{baseUrl}}/api/folders?parent_id={{parentId}}&page={{page}}&per_page={{perPage}}
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:14:32 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "folder_id": 30,
        "folder_name": "B\u00e0i t\u1eadp",
        "fol_folder_id": 29,
        "created_at": "2025-11-15T10:10:02.000000Z"
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

### Invalid/non-owned parent (404)
GET {{baseUrl}}/api/folders?parent_id=999999
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:14:53 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Parent folder not found or not owned by user",
    "code": "PARENT_NOT_FOUND",
    "errors": null
  },
  "meta": null
}

### Unauthenticated (401)
GET {{baseUrl}}/api/folders
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:15:03 GMT
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