# PostMate — Tài liệu tính năng theo folder

Tài liệu nghiệp vụ từng tính năng, dựng từ 9 màn thiết kế trong [`Design/`](../Design) và bản phân tích tổng thể [`PHAN_TICH_HE_THONG.md`](../PHAN_TICH_HE_THONG.md).

Mỗi folder tính năng gồm 3 file:
- **README.md** — tổng quan, thành phần giao diện, bảng dữ liệu liên quan.
- **NGHIEP_VU.md** — quy tắc nghiệp vụ, luồng xử lý, ngoại lệ.
- **HAM_XU_LY.md** — đặc tả các hàm service/worker xử lý luồng nghiệp vụ.

## Danh sách tính năng

| Tính năng | Màn thiết kế | Tài liệu |
|-----------|--------------|----------|
| [Dashboard](./Dashboard) | homepage.png | Tổng quan sức khỏe hệ thống |
| [Tạo bài viết](./TaoBaiViet) | createContent.png | Soạn nội dung & lên lịch |
| [Lịch đăng](./LichDang) | calendar.png | Quản lý bài đã lên lịch + timeline thực thi |
| [Bài viết](./BaiViet) | post.png | Bài đã đăng & hiệu suất |
| [Tài khoản Facebook](./TaiKhoanFacebook) | facebook.png | Quản lý tài khoản đăng bài |
| [Fanpage](./Fanpage) | fanpage.png | Quản lý fanpage liên kết |
| [Trình duyệt](./TrinhDuyet) | browser.png | Chrome profile chống phát hiện |
| [Cookie](./Cookie) | cookies.png | Quản lý phiên đăng nhập |
| [Cài đặt](./CaiDat) | setting.png | Cấu hình hệ thống |

## Kiến trúc tổng quan
- **API-first (Graph API)** là kênh mặc định cho fanpage người dùng sở hữu; **browser anti-detect** chỉ là fallback.
- Chi tiết kiến trúc, CSDL (ERD + bảng), cơ chế chống block: xem [PHAN_TICH_HE_THONG.md](../PHAN_TICH_HE_THONG.md).

## Thành phần dùng chung (cross-feature)
- **QueueService** — hàng đợi job đăng bài (định nghĩa ở [LichDang](./LichDang/HAM_XU_LY.md)).
- **PostExecutor / worker** — thực thi đăng bài API/browser ([LichDang](./LichDang/HAM_XU_LY.md)).
- **ActivityLogService** — ghi nhật ký, mọi feature gọi khi có sự kiện.
- **MetricsSyncService** — đồng bộ hiệu suất từ Facebook ([BaiViet](./BaiViet/HAM_XU_LY.md)).
