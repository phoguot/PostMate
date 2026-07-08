# Cookie — Nghiệp vụ

## 1. Quy tắc nghiệp vụ

### 1.1. Trạng thái cookie
| Trạng thái | Điều kiện | Ảnh hưởng |
|-----------|-----------|-----------|
| `valid` (Hợp lệ) | Còn hạn, session còn sống | Được dùng đăng bài |
| `expiring` (Sắp hết hạn) | `expires_at` ≤ ngưỡng (VD 7 ngày) | Cảnh báo, ưu tiên làm mới |
| `invalid` (Không hợp lệ) | Hết hạn / session chết / bị FB thu hồi | Chặn đăng, cần đăng nhập lại |

### 1.2. Nguyên tắc quản lý phiên (anti-detect)
- Đăng bài **bằng cookie** thay vì user/pass → giảm số lần login → giảm nghi ngờ.
- **Làm mới chủ động** trước khi hết hạn (mở profile, refresh session) thay vì để hết hạn rồi login lại từ đầu.
- Giữ cookie gắn đúng **thiết bị/UA/IP** đã tạo ra nó — **không "port" cookie** sang máy cấu hình lệch (sẽ bị checkpoint).
- `cookie_blob` phải **mã hóa at-rest**; chỉ giải mã khi nạp vào profile.

### 1.3. Ràng buộc gắn kết
- Mỗi cookie thuộc **1 tài khoản** và gắn với **1 profile**.
- IP đăng nhập (proxy) của cookie phải nhất quán với tài khoản (không nhảy IP/quốc gia).

## 2. Luồng các thao tác chính

### Làm mới cookie (refresh)
```
Bấm "Làm mới cookie"
   │
   ▼
Mở profile (đúng UA/proxy) → truy cập Facebook với cookie hiện tại
   │
   ├── Session còn sống → FB cấp cookie mới → lưu (valid, expires_at mới)
   └── Session chết      → status = invalid → gợi ý "Đăng nhập lại"
   │
   ▼
Cập nhật last_login_at/ip, ghi activity_logs ("Refresh cookie")
```

### Đăng nhập (tạo cookie mới)
```
Bấm "Đăng nhập" → login qua profile (user/pass/2FA hoặc import cookie)
   → thành công: tạo cookie valid, gắn account + profile
```

### Xuất / Xóa
- **Xuất cookie**: xuất `cookie_blob` (giải mã) ra file để backup/di chuyển — cảnh báo bảo mật, ghi log.
- **Xóa**: vô hiệu cookie; nếu là cookie đang dùng của account → account chuyển cần đăng nhập lại.
- **Làm mới toàn bảng**: chạy `refresh` hàng loạt cho các cookie `expiring`.

## 3. Ngoại lệ / biên
- Refresh thất bại nhiều lần → đánh dấu `invalid`, dừng giao job cho account.
- Cookie `expiring` không được làm mới kịp → tự động thử refresh theo cron trước hạn.
- Xuất cookie là hành động nhạy cảm → yêu cầu xác nhận, chỉ role đủ quyền, ghi activity_logs đầy đủ.
