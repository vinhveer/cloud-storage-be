6.4. API: GET /api/folders/{id}/contents 
Description: Lấy danh sách file và folder con trong một folder cụ thể.

### 1) Happy path — list contents
GET {{baseUrl}}/api/folders/29/contents
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:28:20 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "folders": [
      {
        "folder_id": 30,
        "folder_name": "B\u00e0i t\u1eadp",
        "created_at": "2025-11-15T10:10:02.000000Z"
      }
    ],
    "files": [
      {
        "file_id": 46,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "last_opened_at": "2025-11-15T09:47:39.000000Z"
      }
    ]
  },
  "error": null,
  "meta": null
}


### 1a) List root contents (authenticated)
GET {{baseUrl}}/api/folders/0/contents
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:37:11 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "folders": [
      {
        "folder_id": 29,
        "folder_name": "Folder s\u1ebd \u0111\u01b0\u1ee3c move t\u1edbi",
        "created_at": "2025-11-15T09:15:08.000000Z"
      }
    ],
    "files": [
      {
        "file_id": 50,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "last_opened_at": null
      },
      {
        "file_id": 49,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_2.pdf",
        "file_size": 852849,
        "mime_type": "application/pdf",
        "file_extension": "pdf",
        "last_opened_at": null
      }
    ]
  },
  "error": null,
  "meta": null
}


### 2) Unauthenticated — 401
GET {{baseUrl}}/api/folders/12/contents
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:28:55 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Unauthenticated",
    "code": "UNAUTHENTICATED",
    "errors": null
  },
  "meta": null
}

### 3) Not found / no access — 404
GET {{baseUrl}}/api/folders/999999/contents
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:29:05 GMT
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