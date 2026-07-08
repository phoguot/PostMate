# Cài đặt — Cấu hình hệ thống

> Màn thiết kế: [`Design/setting.png`](../../Design/setting.png)

## Mục đích
Cấu hình hệ thống PostMate: thông tin tài khoản, ngôn ngữ/múi giờ, kết nối Facebook (Fanpage, Token & Quyền), mặc định khi tạo bài, tùy chọn hệ thống, thành viên, bảo mật, thanh toán và thông tin hệ thống.

## Thành phần giao diện

| Nhóm menu | Nội dung |
|-----------|----------|
| **Tổng quan** | Thông tin tài khoản (vai trò Admin), ngôn ngữ & múi giờ |
| **Tài khoản** | Hồ sơ người dùng, đổi mật khẩu |
| **Facebook** | Fanpage, **Token & Quyền** — kết nối Meta App / cấp Page Access Token, hạn token, làm mới |
| **Thông báo** | Kênh & sự kiện nhận thông báo |
| **AI & Nội dung** | Cấu hình AI Agent, hiển thị gợi ý AI |
| **Lịch đăng** | Mặc định khi tạo bài (fanpage/loại nội dung/trạng thái/thời gian) |
| **Thành viên** | Mời & phân quyền thành viên |
| **Bảo mật** | 2FA, phiên đăng nhập, khóa mã hóa |
| **Thanh toán** | Gói dịch vụ (Business), hóa đơn |
| **Nhật ký hoạt động** | Audit log toàn hệ thống |

### Khối nổi bật
- **Cài đặt mặc định khi tạo bài**: fanpage, loại nội dung (Bài viết ảnh + văn bản), trạng thái (Lên lịch), thời gian mặc định (09:00).
- **Tùy chọn hệ thống** (toggle): tự rút gọn link · tự lưu nháp · hiển thị gợi ý AI · xác nhận trước khi đăng.
- **Thông tin hệ thống**: phiên bản (v1.2.0), cập nhật cuối, máy chủ, **dung lượng (2.45/10 GB)**, nút **Sao lưu ngay**.
- **Kênh đăng ưu tiên** (API-first): `preferred_channel = graph_api`, `allow_browser_fallback`.

## Bảng dữ liệu liên quan
- `settings` — cấu hình theo user (kèm `preferred_channel`, `allow_browser_fallback`)
- `users` / `members` — tài khoản & thành viên
- `meta_app_credentials` — cấu hình Meta App (Token & Quyền)
- `fanpages` — `page_access_token`, `token_expires_at`, `api_enabled`

## Tài liệu liên quan
- [Nghiệp vụ](./NGHIEP_VU.md)
- [Các hàm xử lý luồng nghiệp vụ](./HAM_XU_LY.md)
