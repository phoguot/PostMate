# Trình duyệt — Các hàm xử lý luồng nghiệp vụ

> Đặc tả service + orchestrator layer (chưa có code — thiết kế hàm đề xuất).

## BrowserProfileService

### `getProfileStats(userId): ProfileStats`
Đếm: total, running, stopped, offline; kèm số server.

### `listProfiles(userId, filter, page): ProfileRow[]`
Join `browser_profiles` + `servers` (IP) + `facebook_accounts` + `proxies`; kèm cpu_percent, ram_mb, last_active_at.

### `getProfileDetail(profileId): ProfileDetail`
Trả về profile_id, email, server, mode (headless), chrome_version, os, started_at, uptime, cookie, user_agent, fingerprint tóm tắt.

### `startProfile(profileId)`
```
1. server = load(profile.server_id); check status = online
2. check server.running_instances < server.max_instances → else xếp hàng
3. Khởi tạo Chrome:
     --headless, proxy = profile.proxy, user-agent = profile.user_agent,
     inject fingerprint_json (canvas/webgl/fonts/timezone),
     navigator.webdriver = false
4. Nạp cookie tài khoản gắn kèm
5. status = running, started_at = now, ghi activity_logs
```

### `stopProfile(profileId)`
Đóng Chrome instance, giải phóng tài nguyên → `status = stopped`.

### `restartProfile(profileId)`
`stopProfile` → `startProfile`, **giữ nguyên** fingerprint/proxy/UA.

### `openProfile(profileId)`
Mở phiên xem trực tiếp (non-headless hoặc remote debug) để thao tác/kiểm tra thủ công.

### `deleteProfile(profileId)`
Chặn nếu đang `running` với job `processing`; yêu cầu gỡ tài khoản gắn kèm; xóa profile + dữ liệu cô lập.

## ResourceMonitor (agent trên mỗi server)

### `reportResourceCron()` *(mỗi 30s)*
Đẩy cpu_percent, ram_mb, uptime của từng profile + cpu/ram của server → cập nhật `browser_profiles`, `servers`.

### `detectCrashedProfiles()`
Profile khai `running` nhưng process chết → set trạng thái lỗi, cho phép restart, retry job đang chạy.

## Orchestrator (chọn profile cho job browser)

### `pickProfileForJob(post): BrowserProfile | null`
```
1. profile = profile gắn với account của fanpage
2. Nếu offline → thử profile khác cùng account (nếu có) hoặc null
3. Nếu stopped → startProfile()
4. Kiểm tra rate limit của account (bài/giờ) → hoãn nếu vượt
5. Trả về profile sẵn sàng
```

## Phụ thuộc
- Cookie nạp vào profile — xem [Cookie/HAM_XU_LY.md](../Cookie/HAM_XU_LY.md)
- Worker dùng profile để đăng — xem [LichDang/HAM_XU_LY.md](../LichDang/HAM_XU_LY.md)
