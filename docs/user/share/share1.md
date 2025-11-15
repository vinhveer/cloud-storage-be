7.1. API: POST /api/shares (api này tạo share, thêm recipients, update quyền share luôn, sorry lười nên gộp)
Description: Chia sẻ file hoặc folder cho người dùng khác

### 7) Successful share — create new share (expect share_created = true, added_user_ids includes recipients)
POST {{base}}/api/shares
Accept: {{json}}
Authorization: Bearer {{token}}
Content-Type: {{json}}

{
  "shareable_type": "file",
  "shareable_id": 58,
  "user_ids": [15, 8],
  "permission": "edit"
}

HTTP/1.1 201 Created
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:51:35 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "share": {
      "share_id": 4,
      "shareable_type": "file",
      "shareable_id": 58,
      "user_id": 5,
      "created_at": "2025-11-15 15:51:35",
      "shared_with": [
        {
          "user_id": 8,
          "name": "tritt",
          "permission": "edit"
        },
        {
          "user_id": 15,
          "name": "tritt",
          "permission": "edit"
        }
      ]
    },
    "share_created": true,
    "added_user_ids": [
      8,
      15
    ],
    "updated_user_ids": [],
    "skipped_user_ids": []
  },
  "error": null,
  "meta": null
}

### 8) Re-share same permission — expect share_created = false, added_user_ids empty for existing recipients, skipped_user_ids list existing ones
POST {{base}}/api/shares
Accept: {{json}}
Authorization: Bearer {{token}}
Content-Type: {{json}}

{
  "shareable_type": "file",
  "shareable_id": 58,
  "user_ids": [15, 8],
  "permission": "edit"
}

HTTP/1.1 201 Created
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:52:32 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "share": {
      "share_id": 4,
      "shareable_type": "file",
      "shareable_id": 58,
      "user_id": 5,
      "created_at": "2025-11-15 15:51:35",
      "shared_with": [
        {
          "user_id": 8,
          "name": "tritt",
          "permission": "edit"
        },
        {
          "user_id": 15,
          "name": "tritt",
          "permission": "edit"
        }
      ]
    },
    "share_created": false,
    "added_user_ids": [],
    "updated_user_ids": [],
    "skipped_user_ids": [
      8,
      15
    ]
  },
  "error": null,
  "meta": null
}

### 9) Re-share with changed permission — expect updated_user_ids to include recipients whose permission changed
POST {{base}}/api/shares
Accept: {{json}}
Authorization: Bearer {{token}}
Content-Type: {{json}}

{
  "shareable_type": "file",
  "shareable_id": 58,
  "user_ids": [15, 8],
  "permission": "view"
}

HTTP/1.1 201 Created
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:52:58 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "share": {
      "share_id": 4,
      "shareable_type": "file",
      "shareable_id": 58,
      "user_id": 5,
      "created_at": "2025-11-15 15:51:35",
      "shared_with": [
        {
          "user_id": 8,
          "name": "tritt",
          "permission": "view"
        },
        {
          "user_id": 15,
          "name": "tritt",
          "permission": "view"
        }
      ]
    },
    "share_created": false,
    "added_user_ids": [],
    "updated_user_ids": [
      8,
      15
    ],
    "skipped_user_ids": []
  },
  "error": null,
  "meta": null
}

### 10) Add new and existing users — expect added_user_ids and skipped/updated as appropriate
POST {{base}}/api/shares
Accept: {{json}}
Authorization: Bearer {{token}}
Content-Type: {{json}}

{
  "shareable_type": "file",
  "shareable_id": 58,
  "user_ids": [15, 8, 2],
  "permission": "view"
}

HTTP/1.1 201 Created
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:53:13 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "share": {
      "share_id": 4,
      "shareable_type": "file",
      "shareable_id": 58,
      "user_id": 5,
      "created_at": "2025-11-15 15:51:35",
      "shared_with": [
        {
          "user_id": 2,
          "name": "Kip Rippin",
          "permission": "view"
        },
        {
          "user_id": 8,
          "name": "tritt",
          "permission": "view"
        },
        {
          "user_id": 15,
          "name": "tritt",
          "permission": "view"
        }
      ]
    },
    "share_created": false,
    "added_user_ids": [
      2
    ],
    "updated_user_ids": [],
    "skipped_user_ids": [
      8,
      15
    ]
  },
  "error": null,
  "meta": null
}

### 1) Unauthenticated -> expect 401
POST {{base}}/api/shares
Accept: {{json}}
Content-Type: {{json}}

{
  "shareable_type": "file",
  "shareable_id": 57,
  "user_ids": [8],
  "permission": "view"
}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:11:46 GMT
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

### 2) Validation error - missing fields 422
POST {{base}}/api/shares
Accept: {{json}}
Authorization: Bearer {{token}}
Content-Type: {{json}}

{}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:11:57 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Validation failed",
    "code": "VALIDATION_FAILED",
    "errors": {
      "shareable_type": [
        "The shareable type field is required."
      ],
      "shareable_id": [
        "The shareable id field is required."
      ],
      "user_ids": [
        "The user ids field is required."
      ],
      "permission": [
        "The permission field is required."
      ]
    }
  },
  "meta": null
}

### 3) Invalid shareable_type 422 
POST {{base}}/api/shares
Accept: {{json}}
Authorization: Bearer {{token}}
Content-Type: {{json}}

{
  "shareable_type": "invalid_type",
  "shareable_id": 64,
  "user_ids": [8],
  "permission": "edit"
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:12:16 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Validation failed",
    "code": "VALIDATION_FAILED",
    "errors": {
      "shareable_type": [
        "The selected shareable type is invalid."
      ]
    }
  },
  "meta": null
}

### 4) Non-existing shareable_id 404
POST {{base}}/api/shares
Accept: {{json}}
Authorization: Bearer {{token}}
Content-Type: {{json}}

{
  "shareable_type": "file",
  "shareable_id": 999999,
  "user_ids": [2],
  "permission": "view"
}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:12:30 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Shareable not found",
    "code": "NOT_FOUND",
    "errors": null
  },
  "meta": null
}

### 5) Empty recipients 422
POST {{base}}/api/shares
Accept: {{json}}
Authorization: Bearer {{token}}
Content-Type: {{json}}

{
  "shareable_type": "file",
  "shareable_id": 1,
  "user_ids": [],
  "permission": "view"
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:12:40 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Validation failed",
    "code": "VALIDATION_FAILED",
    "errors": {
      "user_ids": [
        "The user ids field is required."
      ]
    }
  },
  "meta": null
}

### 6) Invalid permission 422
POST {{base}}/api/shares
Accept: {{json}}
Authorization: Bearer {{token}}
Content-Type: {{json}}

{
  "shareable_type": "file",
  "shareable_id": 1,
  "user_ids": [2],
  "permission": "invalid"
}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:12:51 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Validation failed",
    "code": "VALIDATION_FAILED",
    "errors": {
      "permission": [
        "The selected permission is invalid."
      ]
    }
  },
  "meta": null
}