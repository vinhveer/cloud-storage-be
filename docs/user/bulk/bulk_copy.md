Description: Sao chép nhiều file hoặc folder sang thư mục khác. Mỗi bản sao sẽ tạo file mới (hoặc folder mới) có display_name thêm hậu tố “(Copy)”.

### 1) Happy path — copy files and folders
POST {{baseUrl}}/api/bulk/bulk-copy
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "file_ids": [49, 50],
  "folder_ids": [34],
  "destination_folder_id": null
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:10:04 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "success": true,
    "message": "Items copied successfully.",
    "copied": {
      "files": [
        {
          "original_id": 49,
          "new_id": 52
        },
        {
          "original_id": 50,
          "new_id": 53
        }
      ],
      "folders": [
        {
          "original_id": 34,
          "new_id": 35
        }
      ]
    }
  },
  "error": null,
  "meta": null
}

### 2) Unauthenticated — 401
POST {{baseUrl}}/api/bulk/bulk-copy
Accept: application/json
Content-Type: application/json

{
  "file_ids": [5],
  "destination_folder_id": 10
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:10:37 GMT
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

### 3) Destination not owned / permission denied — expect 400 with COPY_FAILED
POST {{baseUrl}}/api/bulk/bulk-copy
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "file_ids": [5],
  "destination_folder_id": 1
}

HTTP/1.1 400 Bad Request
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:10:48 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Destination folder not found or permission denied.",
    "code": "COPY_FAILED",
    "errors": null
  },
  "meta": null
}