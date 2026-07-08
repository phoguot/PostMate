# Cookie — Các hàm xử lý luồng nghiệp vụ

> Đặc tả service layer (chưa có code — thiết kế hàm đề xuất).

## CookieService

### `getCookieStats(userId): CookieStats`
```
total    = count(cookies)
valid    = count(status = 'valid')
expiring = count(status = 'expiring')   // expires_at <= now + 7d
invalid  = count(status = 'invalid')
```

### `listCookies(userId, filter, page): CookieRow[]`
Join `cookies` + `facebook_accounts` + `browser_profiles` + `fanpages`; tính "còn N ngày" từ `expires_at`.

### `getCookieDetail(cookieId): CookieDetail`
Trả về size_kb, status, expires_at, last_login_ip (+ cờ quốc gia), device, user_agent. **Không** trả `cookie_blob` thô ra UI.

### `refreshCookie(cookieId): RefreshResult`
```
1. cookie = load; profile = cookie.browser_profile
2. Mở/dùng profile (đúng UA/proxy) → truy cập FB với cookie hiện tại
3. Session còn sống:
     - FB set cookie mới → encrypt → cập nhật cookie_blob, expires_at, status = valid
     - cập nhật last_login_at/ip
4. Session chết:
     - status = invalid
     - account liên quan → gợi ý re-login
5. Ghi activity_logs ("Refresh cookie — <account>")
```

### `refreshAllExpiring(userId)`
Lặp `refreshCookie` cho mọi cookie `expiring` (nút "Làm mới" toàn bảng); chạy nền, báo kết quả tổng hợp.

### `loginCreateCookie(accountId, method, payload): Cookie`
Tạo cookie mới qua đăng nhập (user/pass/2FA) hoặc import cookie; encrypt `cookie_blob`, gắn account + profile.

### `exportCookie(cookieId): FileRef`
```
1. Kiểm tra quyền (role đủ mạnh)
2. Giải mã cookie_blob → xuất file
3. Ghi activity_logs (level = warning, hành động nhạy cảm)
```

### `deleteCookie(cookieId)`
Vô hiệu cookie; nếu đang là cookie hoạt động của account → account cần đăng nhập lại; ghi log.

### `loadCookieIntoProfile(cookieId, profile)` *(dùng bởi worker)*
Giải mã `cookie_blob` → set vào Chrome instance trước khi truy cập FB. Chỉ giải mã tại thời điểm dùng.

## Cron

### `cookieRefreshCron()` *(hằng ngày)*
```
1. Quét cookie expires_at <= now + 3d và status != invalid
2. refreshCookie() chủ động trước hạn
3. Cập nhật KPI + cảnh báo Dashboard
```

## Bảo mật
- `cookie_blob` mã hóa at-rest (AES); khóa quản lý qua secret manager.
- Mọi thao tác export/xóa đều audit qua `activity_logs`.

## Phụ thuộc
- Nạp cookie khi khởi động profile — xem [TrinhDuyet/HAM_XU_LY.md](../TrinhDuyet/HAM_XU_LY.md)
- Re-login khi cookie invalid — xem [TaiKhoanFacebook/HAM_XU_LY.md](../TaiKhoanFacebook/HAM_XU_LY.md)
