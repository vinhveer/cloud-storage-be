Description:
Trả về đường dẫn xem trước nội dung file (preview).
Tùy theo loại file mà backend xử lý khác nhau:

### 1) Happy path — authenticated PDF/image/video/audio preview (200)
GET {{baseUrl}}/api/files/50/preview
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:15:32 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Preview URL generated successfully.",
    "file": {
      "file_id": 50,
      "display_name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf",
      "mime_type": "application/pdf",
      "file_size": 852849
    },
    "preview_url": "http://localhost:8000/storage/files/50/v1/a87d0be8-69c2-4f4c-ba3c-78367c208a76.pdf?expires=1763216132&signature=f1569845682bbf31378476db5dbf71d7c9f34fc9fa2666c857a6b252af15a710",
    "expires_in": 3600
  },
  "error": null,
  "meta": null
}

### 3) Unauthenticated — missing auth and token (401)
GET {{baseUrl}}/api/files/42/preview
Accept: application/json

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:16:06 GMT
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

### 4) Forbidden — authenticated but not allowed (403)
GET {{baseUrl}}/api/files/10/preview
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 403 Forbidden
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:16:18 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "File not accessible",
    "code": "FORBIDDEN",
    "errors": null
  },
  "meta": null
}

### 7) TXT preview — converted to simple HTML preview (200)
GET {{baseUrl}}/api/files/55/preview
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:22:21 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "message": "Preview URL generated successfully.",
    "file": {
      "file_id": 55,
      "display_name": "ss.txt",
      "mime_type": "text/plain",
      "file_size": 15
    },
    "preview_url": "http://localhost:8000/storage/previews/55/v1/preview.html?expires=1763216541&signature=96fc83f6b3cc4a3827f3b5c1d99a02f9df7cb54b8b2acc1c79882e4d9ade5b03",
    "expires_in": 3600
  },
  "error": null,
  "meta": null
}

### 6) Office document conversion unavailable (501)
GET {{baseUrl}}/api/files/56/preview
Accept: application/json
Authorization: Bearer {{token}}

HTTP/1.1 501 Not Implemented
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:49:08 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "Preview conversion for this file type requires an external converter and is not available",
    "code": "PREVIEW_CONVERSION_UNAVAILABLE",
    "errors": null
  },
  "meta": null
}

### 5) Unsupported file type — preview not supported (400)
GET {{baseUrl}}/api/files/55/preview
Accept: application/json
Authorization: Bearer {{token}}

### Expect: 400 PREVIEW_NOT_SUPPORTED with message "Preview not supported for this file type."
