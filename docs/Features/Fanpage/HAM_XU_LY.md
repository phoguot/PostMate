# Fanpage — Các hàm xử lý luồng nghiệp vụ

> Đặc tả service layer (chưa có code — thiết kế hàm đề xuất).

## FanpageService

### `getFanpageStats(userId): FanpageStats`
Đếm: total, active, needRelogin, inactive.

### `listFanpages(userId, filter, page): FanpageRow[]`
Join `fanpages` + `facebook_accounts` + `browser_profiles` + cookie mới nhất; tính `canPost` cho mỗi dòng.

### `getFanpageDetail(fanpageId): FanpageDetail`
Trả về tên, danh mục, likes/followers, url, tài khoản quản lý, cookie, kênh đăng (api/browser), `capabilities`, `can_post` + lý do.

### `computeCanPost(fanpage): { canPost, reason }`
```
if fanpage.status != active           → false, "Trang không hoạt động"
if channel = graph_api:
   token && token_expires_at > now    → true : false, "Token hết hạn"
if channel = browser:
   cookie.valid && profile != offline → true : false, "Cookie/profile lỗi"
```
Dùng chung bởi enqueue, màn Tạo bài viết (`validatePostability`).

### `refreshPageStats(fanpageId)` *(cron/định kỳ)*
Đồng bộ likes_count, followers_count từ FB (API insights hoặc scrape).

### `unlinkFanpage(fanpageId)`
```
1. Cảnh báo post scheduled sẽ bị hủy
2. QueueService.cancelFanpageJobs(fanpageId)
3. Thu hồi page_access_token (nếu API) · gỡ mapping page ↔ account
4. Ghi activity_logs
```

### `reLoginFromFanpage(fanpageId)`
Ủy quyền `FacebookAccountService.reLogin(account)` → sau đó `recomputeCanPostForAccount(accountId)`.

### `recomputeCanPostForAccount(accountId)`
Tính lại `can_post` cho toàn bộ fanpage thuộc tài khoản (sau login lại / refresh token).

## TokenService (kênh API)

### `refreshPageTokenCron()` *(cron)*
Quét `fanpages` có `token_expires_at` sắp hết → xin token mới từ system user token → cập nhật `page_access_token`, `token_expires_at`.

## Phụ thuộc
- `reLogin()` — xem [TaiKhoanFacebook/HAM_XU_LY.md](../TaiKhoanFacebook/HAM_XU_LY.md)
- `QueueService.cancelFanpageJobs()` — xem [LichDang/HAM_XU_LY.md](../LichDang/HAM_XU_LY.md)
