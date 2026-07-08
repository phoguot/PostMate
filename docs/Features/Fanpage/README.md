# Fanpage — Quản lý fanpage liên kết

> Màn thiết kế: [`Design/fanpage.png`](../../Design/fanpage.png)

## Mục đích
Quản lý các fanpage liên kết với tài khoản: khả năng đăng bài, trạng thái cookie/kênh, và thao tác đăng nhập lại / gỡ liên kết.

## Thành phần giao diện

| Khối | Nội dung |
|------|----------|
| **KPI** | Tổng (15) · Đang hoạt động (12) · Cần đăng nhập lại (2) · Không hoạt động (1) |
| **Bảng** | Fanpage · tài khoản · trình duyệt · trạng thái · cookie · **Khả năng đăng bài** (Có thể / Không thể) · lần đăng gần nhất |
| **Panel chi tiết** | Tên · danh mục · số thích/theo dõi · link · tài khoản quản lý · cookie · khả năng thực hiện · (kênh đăng API/Trình duyệt) |
| **Thao tác** | Chi tiết · Đăng nhập lại · **Gỡ liên kết** |

## Bảng dữ liệu liên quan
- `fanpages` — thực thể chính (kèm `page_access_token`, `token_expires_at`, `api_enabled`)
- `facebook_accounts` — tài khoản quản lý
- `browser_profiles`, `cookies` — nhánh browser fallback
- `posts` — `last_post_at`

## Tài liệu liên quan
- [Nghiệp vụ](./NGHIEP_VU.md)
- [Các hàm xử lý luồng nghiệp vụ](./HAM_XU_LY.md)
