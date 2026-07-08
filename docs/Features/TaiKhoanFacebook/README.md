# Tài khoản Facebook — Quản lý tài khoản đăng bài

> Màn thiết kế: [`Design/facebook.png`](../../Design/facebook.png)
> Popup kết nối tài khoản mới: [`Design/popup_connect_facebook.png`](../../Design/popup_connect_facebook.png)

## Mục đích
Quản lý các tài khoản Facebook dùng để đăng bài: trạng thái hoạt động, cookie, IP đăng nhập, khả năng thao tác và xử lý khi gặp checkpoint.

## Thành phần giao diện

| Khối | Nội dung |
|------|----------|
| **KPI** | Tổng (18) · Đang hoạt động (14) · Cookie sắp hết hạn (2 trong 7 ngày) · Gặp vấn đề (2 cần kiểm tra) |
| **Bảng** | Tài khoản · trình duyệt/profile · trạng thái (Đang hoạt động / Không hoạt động / Gặp vấn đề–Checkpoint) · cookie (còn N ngày) · fanpage liên kết · lần đăng nhập · IP |
| **Panel chi tiết** | Profile · cookie · IP đăng nhập (🇻🇳) · thiết bị · User-Agent · **Khả năng** (đăng bài/upload/bình luận/inbox) · tab Fanpage / Phiên đăng nhập / Nhật ký |
| **Thao tác** | **Đăng nhập lại** · Xóa tài khoản |

## Bảng dữ liệu liên quan
- `facebook_accounts` — thực thể chính
- `cookies` — phiên đăng nhập của tài khoản
- `fanpages` — trang do tài khoản quản lý
- `browser_profiles` — profile gắn 1-1 với tài khoản
- `proxies` — IP đăng nhập
- `activity_logs` — tab Nhật ký

## Tài liệu liên quan
- [Nghiệp vụ](./NGHIEP_VU.md)
- [Các hàm xử lý luồng nghiệp vụ](./HAM_XU_LY.md)
