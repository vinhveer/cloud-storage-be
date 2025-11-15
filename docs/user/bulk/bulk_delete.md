Description: Xóa nhiều file và/hoặc folder cùng lúc. Các file hoặc folder sẽ được chuyển vào thùng rác (is_deleted = true, deleted_at = NOW()), không xóa vĩnh viễn.

### 1) Happy path — 200 OK
POST {{baseUrl}}/api/bulk/bulk-delete
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "file_ids": [53],
  "folder_ids": [34]
}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:13:40 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "success": true,
    "message": "Selected items moved to trash successfully.",
    "deleted": {
      "files": [
        53,
        52
      ],
      "folders": [
        34,
        35
      ]
    },
    "details": {
      "file_result": {
        "requested": [
          53
        ],
        "found": [
          53
        ],
        "not_found": [],
        "not_owned": [],
        "already_deleted": [],
        "deleted": [
          53
        ]
      },
      "folder_result": {
        "requested": [
          34
        ],
        "found": [
          34
        ],
        "not_found": [],
        "not_owned": [],
        "already_deleted": [],
        "deleted_folders": [
          34,
          35
        ],
        "deleted_files": [
          52,
          53
        ]
      }
    }
  },
  "error": null,
  "meta": null
}

### 2) Validation fail — 422 (no payload)
POST {{baseUrl}}/api/bulk/bulk-delete
Accept: application/json
Authorization: Bearer {{token}}
Content-Type: application/json

{}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:14:15 GMT
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
      "payload": [
        "At least one of file_ids or folder_ids must be provided."
      ]
    }
  },
  "meta": null
}

### 3) Unauthenticated — 401
POST {{baseUrl}}/api/bulk/bulk-delete
Accept: application/json
Content-Type: application/json

{
  "file_ids": [1]
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:14:27 GMT
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