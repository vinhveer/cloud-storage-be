12.3. API: GET /api/dashboard/stats 
Description: Thống kê cá nhân (chi tiết hơn tổng quan), gồm phân loại file theo loại, dung lượng, và biểu đồ theo thời gian.

### Get stats with date range (both start and end)
# Expect: 200 with timeline covering the range
GET {{base}}/api/dashboard/stats?start_date=2025-11-01&end_date=2025-11-10
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:04:40 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "file_type_stats": [
      {
        "extension": "pdf",
        "count": 4,
        "total_size": 3411396
      }
    ],
    "storage_timeline": [
      {
        "date": "2025-11-01",
        "uploaded": 0
      },
      {
        "date": "2025-11-02",
        "uploaded": 0
      },
      {
        "date": "2025-11-03",
        "uploaded": 0
      },
      {
        "date": "2025-11-04",
        "uploaded": 0
      },
      {
        "date": "2025-11-05",
        "uploaded": 0
      },
      {
        "date": "2025-11-06",
        "uploaded": 0
      },
      {
        "date": "2025-11-07",
        "uploaded": 0
      },
      {
        "date": "2025-11-08",
        "uploaded": 0
      },
      {
        "date": "2025-11-09",
        "uploaded": 0
      },
      {
        "date": "2025-11-10",
        "uploaded": 0
      }
    ],
    "total_storage_used": 3411396,
    "total_files": 4
  },
  "error": null,
  "meta": null
}

### Get stats with only start_date (end_date omitted)
# Expect: 200 with timeline for that single date
GET {{base}}/api/dashboard/stats?start_date=2025-11-10
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:05:09 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "file_type_stats": [
      {
        "extension": "pdf",
        "count": 4,
        "total_size": 3411396
      }
    ],
    "storage_timeline": [
      {
        "date": "2025-11-10",
        "uploaded": 0
      }
    ],
    "total_storage_used": 3411396,
    "total_files": 4
  },
  "error": null,
  "meta": null
}

### Get stats with only end_date (start_date omitted)
# Expect: 200 with timeline for that single date
GET {{base}}/api/dashboard/stats?end_date=2025-11-10
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 200 OK
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:05:30 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": true,
  "data": {
    "file_type_stats": [
      {
        "extension": "pdf",
        "count": 4,
        "total_size": 3411396
      }
    ],
    "storage_timeline": [
      {
        "date": "2025-11-10",
        "uploaded": 0
      }
    ],
    "total_storage_used": 3411396,
    "total_files": 4
  },
  "error": null,
  "meta": null
}

### Get stats with invalid date format (should return validation error)
GET {{base}}/api/dashboard/stats?start_date=10-11-2025
Accept: {{json}}
Authorization: Bearer {{token}}

HTTP/1.1 500 Internal Server Error
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:05:45 GMT
Content-Type: application/json
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Authorization

{
  "success": false,
  "data": null,
  "error": {
    "message": "The start date field must match the format Y-m-d.",
    "code": "INTERNAL_ERROR",
    "errors": null
  },
  "meta": {
    "exception": "Illuminate\\Validation\\ValidationException",
    "file": "\/var\/www\/html\/cloud-storage-be\/vendor\/laravel\/framework\/src\/Illuminate\/Support\/helpers.php",
    "line": 414
  }
}

### Unauthenticated — should return 401
# Lỗi khi không đăng nhập
GET {{base}}/api/dashboard
Accept: {{json}}

HTTP/1.1 401 Unauthorized
Host: localhost:8000
Connection: close
X-Powered-By: PHP/8.2.29
Cache-Control: no-cache, private
Date: Sat, 15 Nov 2025 13:05:54 GMT
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