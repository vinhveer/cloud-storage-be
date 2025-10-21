## HealthController — Health check

### Endpoint

- `GET /api/health`

### Mục đích

Kiểm tra trạng thái dịch vụ API. Trả về JSON đơn giản `{ "status": "ok" }`.

### Ví dụ

```bash
BASE=http://localhost:8000
curl -sS -X GET "$BASE/api/health" -H "Accept: application/json"
```


