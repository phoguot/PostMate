# Dashboard — Tổng quan hệ thống

> Màn thiết kế: [`Design/homepage.png`](../../Design/homepage.png)

## Mục đích
Cung cấp cái nhìn tổng quan về sức khỏe hệ thống PostMate trong khoảng thời gian người dùng chọn (mặc định 30 ngày, VD: 23/05–23/06). Dữ liệu tự động cập nhật mỗi **30 giây**.

## Thành phần giao diện

| Khối | Nội dung |
|------|----------|
| **5 thẻ KPI** | Tổng bài viết (352) · Đã đăng thành công (298 · 84.7%) · Chờ đăng (24) · Đã lỗi (38) · Đang xử lý (16) — kèm % so sánh kỳ trước |
| **Biểu đồ hiệu suất đăng bài** | Cột chồng theo ngày: thành công / chờ / lỗi |
| **Tỷ lệ trạng thái** | Donut chart phân bổ theo trạng thái post |
| **Bài viết gần đây** | Bảng: bài viết · fanpage · trình duyệt · thời gian · trạng thái |
| **Top fanpage** | Xếp hạng fanpage theo lượt tương tác |
| **Trạng thái hệ thống** | AI Agent (2 đang chạy) · Chrome Instances (8/10) · Cookie (18/20 hợp lệ) · Queue (12 bài đợi, ổn định) · Hệ thống (tốt) |
| **Nhật ký hoạt động** | Stream sự kiện realtime |

## Bảng dữ liệu liên quan
- `posts` — nguồn KPI và biểu đồ
- `post_metrics` — xếp hạng top fanpage theo tương tác
- `browser_profiles` — trạng thái Chrome instances
- `cookies` — sức khỏe cookie
- `activity_logs` — nhật ký hoạt động
- Queue (Redis/DB) — số job đang đợi

## Tài liệu liên quan
- [Nghiệp vụ](./NGHIEP_VU.md)
- [Các hàm xử lý luồng nghiệp vụ](./HAM_XU_LY.md)
