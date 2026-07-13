# Lịch đăng — Quản lý bài đã lên lịch

> Màn thiết kế: [`Design/calendar.png`](../../Design/calendar.png)

## Mục đích
Quản lý và theo dõi các bài viết **đã lên lịch**: trạng thái, số lần thử, timeline thực thi từng bước; hỗ trợ sửa/nhân bản/xóa trước giờ đăng.

## Thành phần giao diện

| Khối | Nội dung |
|------|----------|
| **KPI** | Tổng (128) · Đã đăng (96) · Chờ đăng (24) · Đã lỗi (8) · Hết hạn (0) |
| **Bộ lọc** | Trạng thái · fanpage · trình duyệt · khoảng ngày |
| **Bảng** | Bài viết · fanpage · trình duyệt/profile · thời gian đăng · trạng thái · **Lần thử (1/3)** · tạo lúc |
| **Panel chi tiết** | Thông tin bài + **Timeline thực thi** từng bước kèm thời lượng (Mở trình duyệt 2s → Truy cập FB 3s → Đi tới fanpage 4s → Điền nội dung 5s → Tải ảnh 12s → Click đăng 2s → Đăng thành công) |
| **Thao tác** | Xem trên Facebook · Nhân bản · Chỉnh sửa · Xóa |

## Bảng dữ liệu liên quan
- `posts` — trạng thái, `scheduled_at`, `attempt_count / max_attempts`
- `post_execution_logs` — timeline từng bước thực thi
- `fanpages`, `browser_profiles` — thông tin hiển thị kèm
- Queue — hàng đợi job đăng bài

## Tài liệu liên quan
- [Nghiệp vụ](./NGHIEP_VU.md)
- [Các hàm xử lý luồng nghiệp vụ](./HAM_XU_LY.md)
- [Luồng xử lý đăng bài thực tế](./LUONG_XU_LY_DANG_BAI.md)
