**Auth API Guide**

Đường dẫn, cách chạy và hướng xử lý cho frontend (tiếng Việt).

---

**Tổng quan**
- Base API: `http://localhost:8000/api`
- Frontend: `http://localhost:3000` (ENV `FRONTEND_URL`)
- Cookie auth: backend sẽ set cookie httpOnly tên `auth_token` sau khi user click link verify (auto-login). Frontend cần `withCredentials: true` để nhận/gửi cookie.

**Endpoints chính**

- POST `/api/register`
  - Mô tả: Tạo user. **Không trả token**. Server sẽ gửi email verify tự động.
  - Body: { name, email, password, password_confirmation }
  - Response (200): { message: 'Registered successfully. Please check your email to verify your account.', user: { id, name, email } }

- POST `/api/email/resend`
  - Mô tả: Gửi lại verification link (không cần auth). Body: { email }
  - Response (200): { message: 'Verification link resent successfully.' }

- GET/POST `/api/email/verify/{id}` (named `api.email.verify`)
  - Mô tả: Verify email bằng signed URL. Route chấp nhận GET (người dùng click link) và POST (API).
  - Hành vi:
    - Nếu request là GET: server validate signature, set `email_verified_at`, tạo personal token và **set cookie httpOnly `auth_token`** rồi redirect về `FRONTEND_URL/email-verified?status=success`.
    - Nếu request là POST: trả JSON `{ message: 'Email verified successfully.' }` và kèm cookie nếu token tạo thành công.
  - Lưu ý bảo mật: cookie httpOnly, SameSite=Lax (local). Production nên dùng Secure + SameSite=None + HTTPS.

- POST `/api/login`
  - Mô tả: Đăng nhập (yêu cầu email đã verified). Trả token (plain text) và user object.
  - Body: { email, password, device_name? }
  - Success: 200 { message:'Login successful.', token: '...', user: {...} }

- POST `/api/auth/logout` (auth required)
  - Mô tả: Xóa token hiện tại server-side và **xóa cookie `auth_token`** (response kèm Set-Cookie để expire cookie).
  - Auth: cookie or Bearer token

- POST `/api/forgot-password`
  - Mô tả: Yêu cầu reset password; gửi email chứa reset link. Body: { email }
  - Response: 200 { message: 'Password reset link sent to your email.' }
  - Implementation notes: Laravel mail may use route named `password.reset`. Project có route redirect `password.reset` → frontend `/reset-password`.

- POST `/api/reset-password`
  - Mô tả: Reset mật khẩu với token. Body: { email, token, password, password_confirmation }
  - Success: 200 { message: 'Password has been reset successfully.' }


**Flow UX (frontend)**

1) Register
  - Gửi POST `/api/register`.
  - Hiển thị thông báo: "Check your email to verify your account".
  - Có thể show button "Resend" → gọi `/api/email/resend`.

2) Verify (auto-login)
  - User click link trong mail → trình duyệt GET tới `/api/email/verify/{id}?expires=...&signature=...` trên backend.
  - Backend validate, set `email_verified_at`, tạo token và set httpOnly cookie `auth_token`, redirect về `http://localhost:3000/email-verified?status=success`.
  - Frontend `/email-verified` page: gọi `GET /api/user` (axios withCredentials) để lấy profile; nếu thành công, user đã auto-login.

3) Login
  - Nếu user không auto-login, frontend gọi `POST /api/login` và lưu token (nếu dùng token) hoặc rely on cookie.
  - Nếu dùng cookie-based flow, frontend **không cần** lưu token; chỉ gọi API với `withCredentials: true`.

  - Nếu email của user chưa được xác thực: backend trả lỗi 403 với mã lỗi `EMAIL_NOT_VERIFIED`.
    - Frontend nên bắt lỗi này và hiển thị một trang/khung (verify UI) cho phép người dùng:
      - Thấy thông báo rõ ràng là email chưa được xác thực và hướng dẫn kiểm tra hộp thư.
      - Nhấn nút `Resend verification email` → gọi `POST /api/email/resend` với `{ email }`.
      - Nếu cần, cho phép nhập lại email để gửi lại link xác thực.
    - UI này giúp người dùng hoàn tất bước verify mà không cần gọi lại flow đăng ký.
    - Ví dụ flow ngắn:
      1. Người dùng submit login → nhận 403 + `EMAIL_NOT_VERIFIED`.
      2. Frontend show `Please verify your email` page với nút `Resend`.
      3. Khi người dùng click `Resend`, gọi `POST /api/email/resend` và show thông báo thành công.
      4. Sau khi người dùng click link trong mail và backend redirect (auto-login), frontend sẽ gọi `GET /api/user` để load profile.

4) Logout
  - Gọi `POST /api/auth/logout` (kèm cookie). Server sẽ delete token và trả Set-Cookie để remove cookie.

5) Forgot/Reset Password
  - Frontend call `POST /api/forgot-password` with { email }.
  - Email contains reset link (redirects to `FRONTEND_URL/reset-password?token=...&email=...`).
  - Frontend `/reset-password` reads token+email from query, user enters new password, then POST `/api/reset-password` with { email, token, password, password_confirmation }.


**Frontend implementation notes**

- Axios config (example):
  - If you rely on cookie auto-login:
    ```js
    axios.defaults.withCredentials = true;
    // call API: axios.get('http://localhost:8000/api/user') will send cookie
    ```

- If you instead use token-based auth from `POST /api/login`:
  - Store token securely (httpOnly cookie is recommended; if using localStorage be mindful of XSS).
  - Add header `Authorization: Bearer <token>` on subsequent requests.

- Reset page
  - Build route `/reset-password` that reads `token` and `email` query params and posts to `/api/reset-password`.


**Mail / Development env**

- `.env` recommendations for dev (Mailtrap):
  ```dotenv
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.mailtrap.io
  MAIL_PORT=2525
  MAIL_USERNAME=your_mailtrap_username
  MAIL_PASSWORD=your_mailtrap_password
  MAIL_FROM_ADDRESS=hello@example.com
  MAIL_FROM_NAME="CloudStorage"
  ```
- Or use `MAIL_MAILER=log` to write mails to `storage/logs/laravel.log` for quick testing.


**Testing (backend-only) tips**

- Create reset token manually in tinker to test reset without frontend/email:
  ```bash
  php artisan tinker
  >>> $user = \App\Models\User::where('email','test@example.com')->first();
  >>> $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
  >>> echo $token;
  ```
- Call reset via curl:
  ```bash
  curl -X POST 'http://localhost:8000/api/reset-password' -H 'Content-Type: application/json' -d '{"email":"test@example.com","token":"<token>","password":"NewPass123!","password_confirmation":"NewPass123!"}'
  ```


**CORS / Cookies**

- `config/cors.php` is configured to allow `FRONTEND_URL` and `supports_credentials => true`.
- Ensure frontend uses `withCredentials: true` and browser will send/receive cookies.
- For production: use HTTPS, set cookie Secure=true and SameSite=None.


**DB / token handling**

- Registration: user created, `email_verified_at` null until verify.
- Verification: backend issues a personal access token (Sanctum/PersonalAccessToken) and sets cookie `auth_token`.
- Logout: server deletes token (if using bearer token) and returns Set-Cookie to clear `auth_token`.


**Errors & responses**

- If email not found or other errors, endpoints return structured JSON via `ApiResponse` trait: `{ success: false, data: null, error: { message, code, errors }, meta: {...} }`.
- Common error codes: `INVALID_VERIFICATION_LINK`, `USER_NOT_FOUND`, `EMAIL_NOT_VERIFIED`, `INVALID_RESET_TOKEN`, `EMAIL_TAKEN`, `INVALID_CREDENTIALS`.


**Quick check-list for frontend integration**

1. Configure axios to `withCredentials = true` if you rely on cookie auth.
2. After register, show message and a Resend button (`POST /api/email/resend`).
3. Handle redirect from verify (`/email-verified?status=success`) — call `/api/user` to get current user.
4. Provide `/reset-password` page to accept token & email and call `/api/reset-password`.
5. For logout, call `/api/auth/logout` and clear client state.

---

File created by backend dev scripts. Nếu bạn muốn tôi tạo thêm một `public/reset-password.html` test page hoặc một Postman collection export, hãy nói tôi sẽ thêm tiếp.
