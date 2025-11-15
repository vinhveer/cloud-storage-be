6.11. API: GET /api/folders/tree
Description: Lấy toàn bộ cây thư mục của người dùng (dạng nested JSON).


### 1) Happy path — 200 OK (authenticated user with folders)
GET {{baseUrl}}/api/folders/tree
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:55:21 GMT
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
        "folder_name": "\u0110\u1ed3 \u00e1n m\u1edbi",
        "children": [
          {
            "folder_id": 30,
            "folder_name": "B\u00e0i t\u1eadp",
            "children": []
          }
        ]
      },
      {
        "folder_id": 31,
        "folder_name": "\u0110\u1ed3 \u00e1n m\u1edbi_copy",
        "children": [
          {
            "folder_id": 32,
            "folder_name": "B\u00e0i t\u1eadp",
            "children": []
          }
        ]
      }
    ]
  },
  "error": null,
  "meta": null
}

### 2) Unauthenticated — 401
GET {{baseUrl}}/api/folders/tree
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:55:41 GMT
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