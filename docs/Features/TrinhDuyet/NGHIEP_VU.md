# Trình duyệt — Nghiệp vụ

## 1. Quy tắc nghiệp vụ

### 1.1. Trạng thái profile
| Trạng thái | Ý nghĩa |
|-----------|---------|
| `running` (Đang chạy) | Chrome instance đang mở, sẵn sàng nhận job |
| `stopped` (Đang dừng) | Đã dừng chủ động, có thể khởi động lại |
| `offline` (Ngoại tuyến) | Server host offline hoặc mất kết nối |

### 1.2. Cô lập & vân tay (fingerprint)
- Mỗi profile là **môi trường cô lập**: cookie/localStorage/cache riêng.
- Vân tay riêng và **cố định theo thời gian**: `chrome_version`, `os`, `user_agent`, Canvas/WebGL/AudioContext/fonts/độ phân giải/timezone (lưu `fingerprint_json`).
- **Nguyên tắc vàng**: không đổi UA/độ phân giải/timezone giữa các phiên — thay đổi đột ngột là tín hiệu bot rõ nhất.
- UA phải khớp OS + phiên bản Chrome khai báo; che dấu dấu hiệu headless (`navigator.webdriver=false`…).

### 1.3. Ràng buộc gắn kết
- **1 profile ↔ 1 tài khoản FB** (1-1).
- **1 profile ↔ 1 proxy cố định** (không nhảy IP).
- Timezone trình duyệt khớp vị trí địa lý của proxy.

### 1.4. Tài nguyên & phân tải
- Mỗi server có `max_instances`; không vượt để tránh quá tải (theo dõi `cpu_usage`/`ram_usage`).
- Phân tải profile trên nhiều server để không tập trung hành vi trên một máy/IP.
- Chạy **Headless nhưng cấu hình đầy đủ** (viewport, WebGL, media devices) để không lộ môi trường server trần.

## 2. Vòng đời & thao tác

```
Khởi động (start) → running → nhận job đăng bài
        │
Dừng (stop) → stopped
        │
Khởi động lại (restart) → stopped → running (giữ nguyên fingerprint/proxy)
        │
Mở (open) → xem trực tiếp phiên (debug/thủ công)
        │
Xóa (delete) → gỡ profile (yêu cầu gỡ tài khoản gắn kèm trước)
```

- Khởi động profile: khởi tạo Chrome với đúng UA/proxy/fingerprint đã lưu, nạp cookie tài khoản.
- Server offline → tất cả profile trên đó chuyển `offline`; job đang chạy → xử lý thất bại/retry.

## 3. Ngoại lệ / biên
- Không đủ tài nguyên (server đạt `max_instances`) → từ chối khởi động thêm, xếp hàng chờ.
- Profile bị crash → tự phát hiện, đánh dấu và cho phép restart; job đang chạy → retry.
- Xóa profile đang `running` với job `processing` → chặn cho tới khi dừng an toàn.
