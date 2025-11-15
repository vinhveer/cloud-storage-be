9.1. API: GET /api/search
Description:
Tìm kiếm toàn cục trong toàn bộ hệ thống (file + folder), có thể lọc theo loại, kích thước, ngày, chủ sở hữu, trạng thái chia sẻ, v.v.


# 1) Unauthenticated -> expect 401

GET {{base}}/api/search
Accept: {{json}}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:30:58 GMT
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

# 2) Authenticated basic search across files + folders

GET {{base}}/api/search
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:37:49 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "data": [
    {
      "type": "folder",
      "id": 34,
      "folder_name": "Folder s\u1ebd \u0111\u01b0\u1ee3c move t\u1edbi",
      "owner": {
        "user_id": 15,
        "name": "tritt",
        "email": "tritt666666@gmail.com"
      },
      "created_at": "2025-11-15 11:35:46"
    },
    {
      "type": "file",
      "id": 50,
      "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf",
      "file_size": 852849,
      "mime_type": "application\/pdf",
      "file_extension": "pdf",
      "owner": {
        "user_id": 15,
        "name": "tritt",
        "email": "tritt666666@gmail.com"
      },
      "created_at": "2025-11-15 10:00:43"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 5
  }
}

# 3) Search only files with extension and size filters

GET {{base}}/api/search?type=file&extension=pdf&size_min=1024&size_max=10485760
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:31:39 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "data": [
    {
      "type": "file",
      "id": 50,
      "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf",
      "file_size": 852849,
      "mime_type": "application\/pdf",
      "file_extension": "pdf",
      "owner": {
        "user_id": 15,
        "name": "tritt",
        "email": "tritt666666@gmail.com"
      },
      "created_at": "2025-11-15 10:00:43"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 4
  }
}

# 4) Search only folders

GET {{base}}/api/search?type=folder&q=r
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:35:55 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "data": [
    {
      "type": "folder",
      "id": 34,
      "folder_name": "Folder s\u1ebd \u0111\u01b0\u1ee3c move t\u1edbi",
      "owner": {
        "user_id": 15,
        "name": "tritt",
        "email": "tritt666666@gmail.com"
      },
      "created_at": "2025-11-15 11:35:46"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 1
  }
}

# 5) Search by date range 

GET {{base}}/api/search?date_from=2025-10-01&date_to=2025-11-15
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:36:18 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "data": [
    {
      "type": "folder",
      "id": 34,
      "folder_name": "Folder s\u1ebd \u0111\u01b0\u1ee3c move t\u1edbi",
      "owner": {
        "user_id": 15,
        "name": "tritt",
        "email": "tritt666666@gmail.com"
      },
      "created_at": "2025-11-15 11:35:46"
    },
    {
      "type": "file",
      "id": 50,
      "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf",
      "file_size": 852849,
      "mime_type": "application\/pdf",
      "file_extension": "pdf",
      "owner": {
        "user_id": 15,
        "name": "tritt",
        "email": "tritt666666@gmail.com"
      },
      "created_at": "2025-11-15 10:00:43"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 5
  }
}

# 7) Pagination

GET {{base}}/api/search?q=r&page=1&per_page=2
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:37:28 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "data": [
    {
      "type": "folder",
      "id": 34,
      "folder_name": "Folder s\u1ebd \u0111\u01b0\u1ee3c move t\u1edbi",
      "owner": {
        "user_id": 15,
        "name": "tritt",
        "email": "tritt666666@gmail.com"
      },
      "created_at": "2025-11-15 11:35:46"
    },
    {
      "type": "file",
      "id": 50,
      "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf",
      "file_size": 852849,
      "mime_type": "application\/pdf",
      "file_extension": "pdf",
      "owner": {
        "user_id": 15,
        "name": "tritt",
        "email": "tritt666666@gmail.com"
      },
      "created_at": "2025-11-15 10:00:43"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 3,
    "total_items": 5
  }
}