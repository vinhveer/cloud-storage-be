Description: Di chuyển nhiều file hoặc folder sang thư mục đích khác (destination_folder_id). Các file và folder đều phải thuộc cùng một người dùng.

### 1) Happy path — 200 OK
POST {{baseUrl}}/api/bulk/bulk-move
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "file_ids": [52, 53],
  "folder_ids": [35],
  "destination_folder_id": 34
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:11:57 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "success": true,
    "message": "Items moved successfully.",
    "moved": {
      "files": [
        52,
        53
      ],
      "folders": [
        35
      ]
    },
    "destination_folder_id": 34
  },
  "error": null,
  "meta": null
}

### 3) Unauthenticated — 401
POST {{baseUrl}}/api/bulk/bulk-move
Accept: application/json
Content-Type: application/json

{
  "file_ids": [10, 11],
  "folder_ids": [4],
  "destination_folder_id": 8
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:12:15 GMT
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

### 4) Destination not found / permission denied — 400
POST {{baseUrl}}/api/bulk/bulk-move
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "file_ids": [10],
  "destination_folder_id": 1
}

HTTP/1.1 400 Bad Request
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:12:26 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Destination folder not found or permission denied.",
    "code": "DESTINATION_ERROR",
    "errors": null
  },
  "meta": null
}

### 5) No items moved — 400 (e.g. ids invalid or not owned)
POST {{baseUrl}}/api/bulk/bulk-move
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "file_ids": [999999],
  "folder_ids": [888888],
  "destination_folder_id": 34
}

HTTP/1.1 400 Bad Request
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:12:44 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "No valid files or folders moved.",
    "code": "DESTINATION_ERROR",
    "errors": null
  },
  "meta": null
}