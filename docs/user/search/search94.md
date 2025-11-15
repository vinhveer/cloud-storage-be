9.4. API: GET /api/search/suggestions
Description:
Trả về danh sách gợi ý autocomplete khi người dùng nhập từ khóa tìm kiếm (ví dụ: hiển thị tên file, folder có chứa chuỗi ký tự).

### Suggestions - missing q (expect 422)
GET {{base}}/api/search/suggestions
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:39:21 GMT
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
      "q": [
        "The q field is required."
      ]
    }
  },
  "meta": null
}

### Suggestions - files only
GET {{base}}/api/search/suggestions?q=b&type=file&limit=1
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:39:35 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "suggestions": [
      {
        "type": "file",
        "id": 50,
        "name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf",
        "full_path": "\/BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf"
      }
    ]
  },
  "error": null,
  "meta": null
}


### Suggestions - folders only
GET {{base}}/api/search/suggestions?q=r&type=folder&limit=2
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:40:05 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "suggestions": [
      {
        "type": "folder",
        "id": 34,
        "name": "Folder s\u1ebd \u0111\u01b0\u1ee3c move t\u1edbi",
        "full_path": ""
      }
    ]
  },
  "error": null,
  "meta": null
}

### Suggestions - all
GET {{base}}/api/search/suggestions?q=r&type=all&limit=10
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:40:15 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "suggestions": [
      {
        "type": "folder",
        "id": 34,
        "name": "Folder s\u1ebd \u0111\u01b0\u1ee3c move t\u1edbi",
        "full_path": ""
      },
      {
        "type": "file",
        "id": 50,
        "name": "BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf",
        "full_path": "\/BaoCaoTTCS_TranThanhTri_64132989_64CNTT3_copy_3.pdf"
      }
    ]
  },
  "error": null,
  "meta": null
}

### Suggestions - limit clamp
GET {{base}}/api/search/suggestions?q=a&limit=200
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 422 Unprocessable Content
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 11:40:41 GMT
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
      "limit": [
        "The limit field must not be greater than 100."
      ]
    }
  },
  "meta": null
}