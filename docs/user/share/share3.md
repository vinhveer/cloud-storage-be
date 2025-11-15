7.3. API: GET /api/shares/{id}
Description: Lấy chi tiết thông tin share (bao gồm danh sách user được chia sẻ)

### 1) Unauthenticated -> expect 401
GET {{base}}/api/shares/1
Accept: {{json}}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:17:55 GMT
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

### 2) Authenticated - not owner -> expect 404
# Use a token that is NOT the owner of share id 1
GET {{base}}/api/shares/9
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 404 Not Found
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:18:11 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "message": "Share not found."
}

### 3) Authenticated - owner -> expect 200 and full payload
GET {{base}}/api/shares/1
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 15:19:14 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "share_id": 1,
  "shareable_type": "file",
  "shareable_name": "BaoCaoBaiTapNhom_Nhom2_CNPM_64CNTT2.docx",
  "created_at": "2025-11-15 14:36:10",
  "shared_by": {
    "user_id": 15,
    "name": "tritt"
  },
  "shared_with": [
    {
      "user_id": 5,
      "name": "Prof. Abigayle Ward DVM",
      "permission": "view"
    },
    {
      "user_id": 6,
      "name": "Jaquelin Larson",
      "permission": "view"
    },
    {
      "user_id": 8,
      "name": "tritt",
      "permission": "view"
    }
  ]
}