# Cookie — Quản lý phiên đăng nhập

> Màn thiết kế: [`Design/cookies.png`](../../Design/cookies.png)

## Mục đích
Quản lý cookie phiên đăng nhập Facebook: theo dõi hạn dùng, làm mới chủ động, xuất/nhập và xóa; giữ tài khoản đăng nhập ổn định mà không cần login lại thường xuyên.

## Thành phần giao diện

| Khối | Nội dung |
|------|----------|
| **KPI** | Tổng (18) · Hợp lệ (14) · Sắp hết hạn (2) · Không hợp lệ (2) |
| **Bảng** | Cookie code · tài khoản · trình duyệt/profile · fanpage liên kết · trạng thái · **hết hạn (còn N ngày)** · lần đăng nhập cuối |
| **Panel chi tiết** | Size · trạng thái · hết hạn · IP đăng nhập (🇻🇳) · thiết bị · User-Agent |
| **Thao tác** | **Đăng nhập** · **Xuất cookie** · **Làm mới cookie** · Xóa · nút **Làm mới** toàn bảng |

## Bảng dữ liệu liên quan
- `cookies` — thực thể chính (`cookie_blob` mã hóa at-rest)
- `facebook_accounts` — tài khoản sở hữu cookie
- `browser_profiles` — profile nạp cookie
- `fanpages` — fanpage liên kết
- `proxies` — IP đăng nhập gắn với cookie

## Tài liệu liên quan
- [Nghiệp vụ](./NGHIEP_VU.md)
- [Các hàm xử lý luồng nghiệp vụ](./HAM_XU_LY.md)
