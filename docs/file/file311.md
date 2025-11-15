3.11. API: GET /api/files/recent
Description: Danh sách files gần đây được mở hoặc upload

### 1) Happy path — recent files (200 OK)
GET {{baseUrl}}/api/files/recent
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:06:28 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "file_id": 50,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf",
        "last_opened_at": "2025-11-15T10:00:43+00:00"
      },
      {
        "file_id": 49,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_2.pdf",
        "last_opened_at": "2025-11-15T10:00:05+00:00"
      },
      {
        "file_id": 48,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy.pdf",
        "last_opened_at": "2025-11-15T09:59:43+00:00"
      },
      {
        "file_id": 47,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_copy.pdf",
        "last_opened_at": "2025-11-15T09:58:44+00:00"
      },
      {
        "file_id": 46,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3.pdf",
        "last_opened_at": "2025-11-15T09:47:39+00:00"
      }
    ]
  },
  "error": null,
  "meta": null
}

### 2) Unauthenticated — 401
GET {{baseUrl}}/api/files/recent
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:06:44 GMT
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

### 3) Optional: limit via query (non-breaking; service defaults to 20) — demonstrates future extensibility
GET {{baseUrl}}/api/files/recent?limit=2
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 10:07:08 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "data": [
      {
        "file_id": 50,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf",
        "last_opened_at": "2025-11-15T10:00:43+00:00"
      },
      {
        "file_id": 49,
        "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_2.pdf",
        "last_opened_at": "2025-11-15T10:00:05+00:00"
      }
    ]
  },
  "error": null,
  "meta": null
}