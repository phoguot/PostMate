# Bài viết — Quản lý bài đã đăng & hiệu suất

> Màn thiết kế: [`Design/post.png`](../../Design/post.png)

## Mục đích
Quản lý và theo dõi các bài **đã đăng** cùng hiệu suất (reach, engagement, like/comment/share…); hỗ trợ đăng lại, chỉnh sửa, xóa và xuất dữ liệu.

## Thành phần giao diện

| Khối | Nội dung |
|------|----------|
| **KPI** | Tổng (352) · Thành công (298) · Thất bại (38) · Đang đăng (16) · Đã xóa (0) |
| **Bảng** | Bài viết · fanpage · trình duyệt · thời gian · trạng thái · **Lượt tương tác** (like/comment) |
| **Panel chi tiết** | Thông tin post + tab **Timeline** + tab **Tương tác** + khối **Hiệu suất** (245 thích · 32 bình luận · 18 chia sẻ · 5.2K tiếp cận · 3.1K tương tác · 156 lưu) |
| **Thao tác** | Xem trên FB · Đăng lại · Chỉnh sửa · Xóa · **Xuất dữ liệu** |

## Bảng dữ liệu liên quan
- `posts` — bài đã đăng (`published`, `failed`, `processing`, `deleted`)
- `post_metrics` — hiệu suất, đồng bộ định kỳ từ Facebook
- `post_execution_logs` — tab Timeline
- `fanpages`, `browser_profiles` — thông tin hiển thị kèm

## Tài liệu liên quan
- [Nghiệp vụ](./NGHIEP_VU.md)
- [Các hàm xử lý luồng nghiệp vụ](./HAM_XU_LY.md)
