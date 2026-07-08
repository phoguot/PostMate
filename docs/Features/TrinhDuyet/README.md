# Trình duyệt — Quản lý Chrome profile chống phát hiện

> Màn thiết kế: [`Design/browser.png`](../../Design/browser.png)

## Mục đích
Quản lý các Chrome profile anti-detect (nhánh thực thi browser fallback): trạng thái chạy, tài nguyên CPU/RAM, vân tay, và điều khiển vòng đời profile.

## Thành phần giao diện

| Khối | Nội dung |
|------|----------|
| **KPI** | Tổng (8 trên 3 máy) · Đang chạy (6) · Đang dừng (1) · Ngoại tuyến (1) |
| **Bảng** | Trình duyệt · profile · tài khoản FB · **máy chủ + IP** · trạng thái · **CPU/RAM** · lần hoạt động gần nhất |
| **Panel chi tiết** | Profile ID · email · máy chủ · chế độ **Headless** · **phiên bản Chrome** · **hệ điều hành** · khởi chạy lúc · thời gian hoạt động · cookie · **User-Agent** |
| **Thao tác** | Mở · Khởi động lại · Dừng · Xóa · Khởi động |

## Bảng dữ liệu liên quan
- `browser_profiles` — thực thể chính (fingerprint, resource, trạng thái)
- `servers` — máy chủ host profile
- `proxies` — IP thoát của profile
- `facebook_accounts` — tài khoản gắn 1-1
- `cookies` — phiên đăng nhập nạp vào profile

## Ghi chú kiến trúc
Đây là **lớp thực thi anti-detect**, chỉ đóng vai trò **fallback** trong kiến trúc API-first. Ở quy mô nhỏ có thể chỉ còn 1–2 profile. Cơ chế chống block: mục 4 trong [PHAN_TICH_HE_THONG.md](../../PHAN_TICH_HE_THONG.md).

## Tài liệu liên quan
- [Nghiệp vụ](./NGHIEP_VU.md)
- [Các hàm xử lý luồng nghiệp vụ](./HAM_XU_LY.md)
