# Cài đặt — Nghiệp vụ

## 1. Quy tắc nghiệp vụ

### 1.1. Cài đặt mặc định khi tạo bài
- Các giá trị này nạp sẵn vào màn **Tạo bài viết** để tăng tốc soạn bài:
  - `default_fanpage_id`, `default_content_type`, `default_status`, `default_post_time`.
- Người dùng vẫn có thể ghi đè từng bài.

### 1.2. Tùy chọn hệ thống (toggle)
| Toggle | Ảnh hưởng |
|--------|-----------|
| `auto_shorten_link` | Tự rút gọn link khi đăng |
| `auto_save_draft` | Composer tự lưu nháp định kỳ |
| `show_ai_suggestions` | Hiện nút/gợi ý AI trong composer |
| `confirm_before_post` | Hiện dialog xác nhận trước khi lên lịch/đăng ngay |

### 1.3. Kênh đăng ưu tiên (API-first)
- `preferred_channel` mặc định `graph_api`.
- `allow_browser_fallback`: cho phép rơi về browser khi API không đáp ứng; khi kích hoạt fallback → cảnh báo.

### 1.4. Facebook — Token & Quyền
- Kết nối **Meta App** (`meta_app_credentials`): app_id, app_secret (mã hóa), system_user_token (mã hóa).
- Cấp **Page Access Token** cho từng fanpage → lưu `fanpages.page_access_token`, theo dõi `token_expires_at`.
- Quyền cần xin (App Review): `pages_manage_posts`, `pages_read_engagement`, `pages_manage_engagement`.
- Hiển thị hạn token + nút **Làm mới**.

### 1.5. Thành viên & phân quyền
- Vai trò: `admin` (toàn quyền), `member` (thao tác nội dung), `viewer` (chỉ xem).
- Mời thành viên qua email; phân quyền theo vai trò.

### 1.6. Dung lượng & sao lưu
- Theo dõi `storage_used / storage_limit` (2.45/10 GB); cảnh báo khi gần đầy.
- **Sao lưu ngay**: tạo bản backup cấu hình + dữ liệu, cập nhật `last_backup_at`.

## 2. Luồng cấu hình chính

```
Mở Cài đặt → chọn nhóm menu
        │
        ├── Sửa mặc định tạo bài → lưu settings → áp dụng cho composer lần sau
        ├── Bật/tắt toggle → lưu ngay (optimistic) → phản ánh toàn hệ thống
        ├── Kết nối Meta App → OAuth → lưu credentials → cấp token cho fanpage
        └── Sao lưu ngay → tạo backup → cập nhật last_backup_at
        │
        ▼
Mọi thay đổi ghi activity_logs (audit)
```

## 3. Ngoại lệ / biên
- Chỉ `admin` được sửa Bảo mật, Thanh toán, Thành viên, Token & Quyền.
- Đổi `preferred_channel` sang browser nhưng không có profile hợp lệ → cảnh báo.
- Kết nối Meta App thất bại (quyền chưa duyệt) → hướng dẫn App Review, giữ nhánh browser.
- Vượt `storage_limit` → chặn upload media mới cho tới khi dọn dẹp hoặc nâng gói.
