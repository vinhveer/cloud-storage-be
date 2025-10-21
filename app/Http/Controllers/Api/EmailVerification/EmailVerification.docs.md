### EmailVerification

API xác thực email.

#### Endpoints

**POST /api/email/verify/{id}** - Xác thực email
**POST /api/email/resend** - Gửi lại email xác thực

#### Response mẫu

```json
{
  "success": true,
  "data": {
    "message": "Email verified successfully"
  },
  "error": null,
  "meta": null
}
```

