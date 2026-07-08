# Fanpage — Nghiệp vụ

## 1. Quy tắc nghiệp vụ

### 1.1. Trạng thái fanpage
| Trạng thái | Ý nghĩa |
|-----------|---------|
| `active` (Đang hoạt động) | Sẵn sàng đăng |
| `need_relogin` (Cần đăng nhập lại) | Cookie hết hạn / token invalid |
| `inactive` (Không hoạt động) | Bị tắt hoặc profile offline |

### 1.2. Khả năng đăng bài (`can_post`)
- **Có thể đăng** khi:
  - Kênh API: `api_enabled = true` **và** `token_expires_at > now`.
  - Kênh browser: cookie `valid` **và** profile không `offline`.
- **Không thể đăng** khi cookie hết hạn / profile offline / token hết hạn.
- `can_post` được tính lại mỗi lần kiểm tra và trước khi enqueue job.

### 1.3. Kênh đăng (API-first)
- Ưu tiên **Graph API** với fanpage người dùng thực sự sở hữu (Page Access Token).
- Chỉ rơi về **browser fallback** khi API không đáp ứng và `settings.allow_browser_fallback = true`.
- Xem mục 6 trong [PHAN_TICH_HE_THONG.md](../../PHAN_TICH_HE_THONG.md).

### 1.4. Thống kê hiển thị
- Số thích/theo dõi (likes_count/followers_count) đồng bộ định kỳ.
- `last_post_at` = thời điểm bài gần nhất đăng thành công lên page.

## 2. Luồng thao tác

### Gỡ liên kết
```
Bấm "Gỡ liên kết"
   │  cảnh báo: các post scheduled của page sẽ bị hủy
   ▼
Hủy job scheduled của fanpage → status expired/deleted
   ▼
Xóa/thu hồi token (kênh API) · gỡ mapping page ↔ account
   ▼
Ghi activity_logs
```

### Đăng nhập lại (từ fanpage)
```
Bấm "Đăng nhập lại" → ủy quyền cho FacebookAccountService.reLogin(account)
   ▼
Sau khi login lại → tính lại can_post cho tất cả fanpage của account
```

## 3. Ngoại lệ / biên
- Token gần hết hạn → chủ động làm mới (system user token), không đợi tới lúc `need_relogin`.
- Gỡ liên kết fanpage đang có job `processing` → chờ job xong hoặc buộc hủy có xác nhận.
- Page bị FB gỡ/đổi quyền → đánh dấu `inactive`, không retry.
