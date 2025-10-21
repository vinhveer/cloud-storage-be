### Password

API quên mật khẩu và reset password.

#### Endpoints

**POST /api/forgot-password** - Gửi link reset password
**POST /api/reset-password** - Reset password với token

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "message": "Password reset link sent to your email"
  },
  "error": null,
  "meta": null
}
```

