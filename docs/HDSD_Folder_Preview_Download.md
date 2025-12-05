# Hướng dẫn sử dụng API Folder Preview & Download

## Mục lục
1. [Giới thiệu](#giới-thiệu)
2. [API Preview Folder](#api-preview-folder)
3. [API Download Folder (ZIP)](#api-download-folder-zip)
4. [Quyền truy cập](#quyền-truy-cập)
5. [Ví dụ sử dụng](#ví-dụ-sử-dụng)
6. [Mã lỗi](#mã-lỗi)

---

## Giới thiệu

Hệ thống cung cấp 2 API mới cho folder:

| API | Mô tả | Quyền yêu cầu |
|-----|-------|---------------|
| `GET /api/folders/{id}/preview` | Xem trước nội dung folder (list files + subfolders + thống kê) | `view` |
| `GET /api/folders/{id}/download` | Tải toàn bộ folder thành file ZIP | `download` |

Cả 2 API đều hỗ trợ:
- **Authenticated user**: Chủ sở hữu hoặc người được share
- **Public link**: Truy cập qua token công khai

---

## API Preview Folder

### Endpoint
```
GET /api/folders/{id}/preview
```

### Headers
| Header | Giá trị | Bắt buộc |
|--------|---------|----------|
| Authorization | `Bearer {token}` | Có (nếu không dùng public link) |
| Accept | `application/json` | Khuyến nghị |

### Query Parameters
| Param | Mô tả | Bắt buộc |
|-------|-------|----------|
| token | Public link token | Không (thay thế cho Authorization) |

### Response thành công (200 OK)
```json
{
  "success": true,
  "data": {
    "folder": {
      "folder_id": 123,
      "folder_name": "My Documents",
      "created_at": "2025-01-15T10:30:00.000Z",
      "updated_at": "2025-12-01T14:20:00.000Z"
    },
    "stats": {
      "total_files": 25,
      "total_folders": 5,
      "total_size": 104857600,
      "total_size_formatted": "100 MB"
    },
    "contents": {
      "folders": [
        {
          "folder_id": 124,
          "folder_name": "Subfolder 1",
          "created_at": "2025-02-10T08:00:00.000Z",
          "updated_at": "2025-11-20T16:45:00.000Z"
        }
      ],
      "files": [
        {
          "file_id": 456,
          "display_name": "report.pdf",
          "file_size": 2048576,
          "mime_type": "application/pdf",
          "file_extension": "pdf",
          "created_at": "2025-03-05T09:15:00.000Z",
          "updated_at": "2025-10-10T11:30:00.000Z",
          "last_opened_at": "2025-12-01T14:00:00.000Z"
        }
      ]
    }
  },
  "error": null,
  "meta": null
}
```

### Giải thích response
- **folder**: Thông tin cơ bản của folder đang xem
- **stats**: Thống kê tổng hợp (đệ quy toàn bộ cây thư mục con)
  - `total_files`: Tổng số file (bao gồm trong subfolder)
  - `total_folders`: Tổng số thư mục con
  - `total_size`: Tổng dung lượng (bytes)
  - `total_size_formatted`: Dung lượng đã format (KB/MB/GB)
- **contents**: Danh sách file và folder **trực tiếp** trong folder này

---

## API Download Folder (ZIP)

### Endpoint
```
GET /api/folders/{id}/download
```

### Headers
| Header | Giá trị | Bắt buộc |
|--------|---------|----------|
| Authorization | `Bearer {token}` | Có (nếu không dùng public link) |
| Accept | `application/zip` | Khuyến nghị |

### Query Parameters
| Param | Mô tả | Bắt buộc |
|-------|-------|----------|
| token | Public link token (phải có quyền `download`) | Không |

### Response thành công
- **Content-Type**: `application/zip`
- **Content-Disposition**: `attachment; filename="FolderName.zip"`
- **Body**: Binary ZIP file

### Cấu trúc file ZIP
```
FolderName.zip
├── file1.pdf
├── file2.docx
├── Subfolder1/
│   ├── file3.jpg
│   └── file4.png
└── Subfolder2/
    └── Nested/
        └── file5.xlsx
```

---

## Quyền truy cập

### Bảng quyền

| Hành động | Quyền `view` | Quyền `download` | Quyền `edit` |
|-----------|:------------:|:----------------:|:------------:|
| Preview folder | ✅ | ✅ | ✅ |
| Download folder (ZIP) | ❌ | ✅ | ✅ |

### Các cách truy cập

#### 1. Chủ sở hữu (Owner)
```bash
# Có toàn quyền
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/folders/123/preview

curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/folders/123/download -o folder.zip
```

#### 2. Người được share
```bash
# Cần được share folder với quyền phù hợp
# Share với quyền 'view': chỉ preview được
# Share với quyền 'download' hoặc 'edit': preview + download được

curl -H "Authorization: Bearer SHARED_USER_TOKEN" \
     http://localhost:8000/api/folders/123/preview
```

#### 3. Public link
```bash
# Preview (cần public link với quyền view trở lên)
curl "http://localhost:8000/api/folders/123/preview?token=PUBLIC_TOKEN"

# Download (cần public link với quyền download)
curl "http://localhost:8000/api/folders/123/download?token=PUBLIC_TOKEN" -o folder.zip
```

---

## Ví dụ sử dụng

### JavaScript/Axios

```javascript
// Preview folder
const previewFolder = async (folderId) => {
  const response = await axios.get(`/api/folders/${folderId}/preview`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  
  console.log('Folder info:', response.data.data.folder);
  console.log('Stats:', response.data.data.stats);
  console.log('Files:', response.data.data.contents.files);
  console.log('Subfolders:', response.data.data.contents.folders);
};

// Download folder as ZIP
const downloadFolder = async (folderId, folderName) => {
  const response = await axios.get(`/api/folders/${folderId}/download`, {
    headers: { Authorization: `Bearer ${token}` },
    responseType: 'blob'
  });
  
  // Create download link
  const url = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', `${folderName}.zip`);
  document.body.appendChild(link);
  link.click();
  link.remove();
};

// Preview via public link
const previewViaPublicLink = async (folderId, publicToken) => {
  const response = await axios.get(`/api/folders/${folderId}/preview`, {
    params: { token: publicToken }
  });
  return response.data;
};
```

### cURL

```bash
# Preview folder
curl -X GET "http://localhost:8000/api/folders/123/preview" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json"

# Download folder
curl -X GET "http://localhost:8000/api/folders/123/download" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -o my_folder.zip

# Preview via public link
curl -X GET "http://localhost:8000/api/folders/123/preview?token=abc123xyz"

# Download via public link
curl -X GET "http://localhost:8000/api/folders/123/download?token=abc123xyz" \
     -o shared_folder.zip
```

---

## Mã lỗi

### HTTP Status Codes

| Status | Mã lỗi | Mô tả |
|--------|--------|-------|
| 401 | `UNAUTHENTICATED` | Chưa đăng nhập và không có public token |
| 403 | `FORBIDDEN` | Không có quyền truy cập folder |
| 404 | `FOLDER_NOT_FOUND` | Folder không tồn tại hoặc đã bị xóa |
| 400 | `EMPTY_FOLDER` | Folder trống, không có file để tải (chỉ download) |
| 500 | `ZIP_ERROR` | Lỗi khi tạo file ZIP |

### Response lỗi mẫu
```json
{
  "success": false,
  "data": null,
  "error": {
    "message": "Folder not accessible",
    "code": "FORBIDDEN",
    "errors": null
  },
  "meta": null
}
```

---

## Lưu ý quan trọng

1. **Dung lượng ZIP**: Với folder lớn (nhiều GB), quá trình tạo ZIP có thể mất thời gian. Nên hiển thị loading indicator trên UI.

2. **Timeout**: Đối với folder rất lớn, có thể cần tăng timeout của request.

3. **Bộ nhớ**: Server cần đủ bộ nhớ để tạo file ZIP tạm thời.

4. **Quyền kế thừa**: Khi share folder, quyền được áp dụng cho toàn bộ nội dung bên trong (subfolder + files).

5. **Public link**: 
   - Với quyền `view`: chỉ preview được
   - Với quyền `download`: preview + download được
   - Kiểm tra `expired_at` và `revoked_at` của public link

---

## Tham khảo thêm

- [API Share](/docs/api-share.md)
- [API Public Link](/docs/api-public-link.md)
- [API File Download](/docs/api-file.md)
