# Cài đặt — Các hàm xử lý luồng nghiệp vụ

> Đặc tả service layer (chưa có code — thiết kế hàm đề xuất).

## SettingsService

### `getSettings(userId): Settings`
Đọc bản ghi `settings` của user; nếu chưa có → tạo mặc định (language, timezone, default_post_time = 09:00, preferred_channel = graph_api…).

### `updateSettings(userId, patch): Settings`
Cập nhật một phần cấu hình; validate (VD timezone hợp lệ, default_fanpage thuộc user); ghi `activity_logs`.

### `toggleOption(userId, key, value)`
Bật/tắt một toggle (`auto_shorten_link`, `auto_save_draft`, `show_ai_suggestions`, `confirm_before_post`) — lưu ngay.

### `getSystemInfo(userId): SystemInfo`
Trả về app_version, last_backup_at, server, storage_used/limit.

### `backupNow(userId): BackupResult`
```
1. Đóng gói cấu hình + dữ liệu người dùng
2. Lưu vào storage backup
3. Cập nhật settings.last_backup_at
4. Ghi activity_logs
```

## MetaAppService (Token & Quyền)

### `connectMetaApp(userId, appId, appSecret): void`
Lưu `meta_app_credentials` (mã hóa app_secret); khởi tạo OAuth flow xin quyền pages_*.

### `issuePageTokens(userId): PageTokenResult[]`
```
1. Dùng system_user_token → lấy Page Access Token cho từng fanpage sở hữu
2. Encrypt → lưu fanpages.page_access_token + token_expires_at
3. Set fanpages.api_enabled = true khi có token hợp lệ
```

### `refreshTokens(userId)`
Ủy quyền `TokenService.refreshPageTokenCron()` (xem [Fanpage/HAM_XU_LY.md](../Fanpage/HAM_XU_LY.md)); hiển thị hạn token còn lại.

## MemberService

### `listMembers(userId): Member[]` / `inviteMember(userId, email, role)` / `updateRole(memberId, role)` / `removeMember(memberId)`
Quản lý thành viên; chỉ `admin` được gọi các hàm ghi. Ghi `activity_logs`.

## Phân quyền (guard dùng chung)

### `requireRole(userId, roles[])`
Chặn thao tác Bảo mật/Thanh toán/Thành viên/Token nếu không đủ vai trò.

## Phụ thuộc
- Giá trị mặc định nạp vào composer — xem [TaoBaiViet/HAM_XU_LY.md](../TaoBaiViet/HAM_XU_LY.md) (`getComposerDefaults`)
- Cấp/làm mới token fanpage — xem [Fanpage/HAM_XU_LY.md](../Fanpage/HAM_XU_LY.md)
