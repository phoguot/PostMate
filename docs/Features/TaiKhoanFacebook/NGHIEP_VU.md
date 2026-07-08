# Tài khoản Facebook — Nghiệp vụ

## 1. Quy tắc nghiệp vụ

### 1.1. Trạng thái tài khoản
| Trạng thái | Ý nghĩa | Ảnh hưởng |
|-----------|---------|-----------|
| `active` (Đang hoạt động) | Cookie hợp lệ, đăng bình thường | Được nhận job đăng |
| `inactive` (Không hoạt động) | Người dùng tạm tắt | Không nhận job |
| `checkpoint` (Gặp vấn đề) | FB yêu cầu xác minh | **Dừng tự động**, cần xử lý thủ công |

### 1.2. Nguyên tắc ổn định tài khoản (anti-detect)
- **1 tài khoản = 1 profile = 1 proxy cố định** (không đổi IP/quốc gia giữa các phiên).
- Vân tay trình duyệt (UA, OS, Chrome version) **nhất quán theo thời gian**.
- Đăng nhập bằng **cookie phiên** thay vì user/pass để giảm số lần login.
- Chi tiết cơ chế: mục 4 trong [PHAN_TICH_HE_THONG.md](../../PHAN_TICH_HE_THONG.md).

### 1.3. Checkpoint
- Khi worker phát hiện FB checkpoint giữa chừng → set account `checkpoint`, **hủy các job còn lại** của tài khoản, cảnh báo lên màn này.
- Không tiếp tục bơm hành động (tránh khóa cứng); người dùng phải **Đăng nhập lại** / xác minh thủ công.

### 1.4. Cookie & cảnh báo hết hạn
- Theo dõi `expires_at` của cookie tài khoản; KPI "Cookie sắp hết hạn" đếm cookie hết hạn trong ≤ 7 ngày.
- Ưu tiên **làm mới chủ động** trước khi hết hạn (xem màn Cookie).

### 1.5. Khả năng (capabilities)
- `capabilities` jsonb quyết định tài khoản được phép: đăng bài, upload ảnh/video, bình luận, trả lời comment, inbox.
- Hệ thống chỉ giao job phù hợp với capability đã bật.

## 2. Luồng đăng nhập lại

```
Người dùng bấm "Đăng nhập lại"
        │
        ▼
Mở profile của tài khoản (đúng UA/proxy/fingerprint đã lưu)
        │
        ▼
Đăng nhập (cookie mới / user-pass / xác minh checkpoint)
        │
        ├── Thành công → lưu cookie mới (status=valid), account=active,
        │                cập nhật last_login_at/ip, ghi activity_logs
        └── Thất bại/checkpoint chưa qua → giữ trạng thái, báo lỗi
```

## 3. Ngoại lệ / biên
- Xóa tài khoản → cần xử lý fanpage liên kết (gỡ liên kết trước) và profile gắn kèm.
- Cookie invalid nhưng account vẫn `active` → tự hạ cấp cảnh báo, chặn nhận job cho tới khi login lại.
- Nhiều tài khoản vô tình dùng chung IP → cảnh báo rủi ro (vi phạm nguyên tắc 1 tài khoản/1 IP).
