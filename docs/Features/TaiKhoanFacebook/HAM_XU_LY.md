# Tài khoản Facebook — Các hàm xử lý luồng nghiệp vụ

> Đặc tả service layer (chưa có code — thiết kế hàm đề xuất).

## FacebookAccountService

### `getAccountStats(userId): AccountStats`
```
total       = count(facebook_accounts)
active      = count(status = 'active')
expiringCookie = count(cookies expires_at <= now + 7d)
checkpoint  = count(status = 'checkpoint')
```

### `listAccounts(userId, filter, page): AccountRow[]`
Join `facebook_accounts` + `browser_profiles` + `cookies` (cookie mới nhất) + `fanpages` (đếm liên kết) + `proxies` (IP).

### `getAccountDetail(accountId): AccountDetail`
Trả về profile, cookie hiện hành, IP đăng nhập, thiết bị, User-Agent, `capabilities`, và các tab:
- `getLinkedFanpages(accountId)`
- `getLoginSessions(accountId)` — lịch sử phiên từ `cookies`/log
- `getActivityLogs(accountId)`

### `connectAccount(userId, permissions?): ConnectResult` *(popup xin quyền — Design/popup_connect_facebook.png)*
```
1. Mở popup cấp quyền Facebook thật (quản lý trang/nội dung, truy cập hiệu suất,
   danh sách fan, tin nhắn) + đăng nhập — performConnect() (hook, chưa có OAuth app thật)
2. Thành công:
     - tạo facebook_accounts mới (status=active, isPrimary = true nếu là tài khoản đầu tiên)
     - nếu hook trả cookie → tạo cookies mới (status=valid)
     - ghi activity_logs ("Kết nối tài khoản")
3. Thất bại → trả lỗi, không tạo account
```

### `reLogin(accountId, credentials?): ReLoginResult`
```
1. profile = load profile của account (UA/proxy/fingerprint cố định)
2. Mở profile → thực hiện đăng nhập:
     - có cookie mới → nạp cookie
     - user/pass → login + xử lý 2FA
     - checkpoint → chờ người dùng xác minh thủ công
3. Thành công:
     - lưu cookie mới (status = valid, expires_at)
     - account.status = active, cập nhật last_login_at/ip/device
     - ghi activity_logs ("Đăng nhập lại — <account>")
4. Thất bại → giữ trạng thái, trả lỗi chi tiết
```

### `markCheckpoint(accountId, reason)` *(gọi từ PostExecutor)*
```
1. account.status = 'checkpoint'
2. QueueService.cancelAccountJobs(accountId)   — hủy job còn lại
3. Ghi activity_logs (level = warning)
4. Đẩy cảnh báo lên Dashboard + màn Tài khoản FB
```

### `deleteAccount(accountId)`
```
1. Kiểm tra fanpage liên kết → yêu cầu gỡ liên kết trước (hoặc gỡ cascade có xác nhận)
2. Vô hiệu cookie, dừng profile gắn kèm
3. Xóa/ẩn account, ghi activity_logs
```

## Hàm giám sát

### `checkCookieHealthCron()` *(cron hằng ngày)*
Quét cookie sắp hết hạn → cập nhật KPI, gợi ý làm mới; account có cookie invalid → chặn nhận job.

## Phụ thuộc
- Làm mới cookie — xem [Cookie/HAM_XU_LY.md](../Cookie/HAM_XU_LY.md)
- `QueueService.cancelAccountJobs()` — xem [LichDang/HAM_XU_LY.md](../LichDang/HAM_XU_LY.md)
