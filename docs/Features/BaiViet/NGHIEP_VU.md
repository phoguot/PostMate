# Bài viết — Nghiệp vụ

## 1. Quy tắc nghiệp vụ

### 1.1. Phạm vi hiển thị
- Màn này tập trung bài **đã qua khâu thực thi**: `published`, `failed`, `processing`, `deleted`.
- Khác với **Lịch đăng** (tập trung bài `scheduled` chưa chạy). Cùng nguồn `posts` nhưng filter trạng thái khác nhau.

### 1.2. Định nghĩa KPI
| KPI | Điều kiện |
|-----|-----------|
| Tổng | Tất cả post đã từng thực thi (trừ `draft`/`scheduled` chưa chạy) |
| Thành công | `status = published`, có `fb_post_id` |
| Thất bại | `status = failed` |
| Đang đăng | `status = processing` |
| Đã xóa | `status = deleted` |

### 1.3. Hiệu suất (metrics)
- Nguồn: `post_metrics`, đồng bộ định kỳ từ Facebook Insights (kênh API) hoặc scrape (kênh browser).
- Chỉ số: likes, comments, shares, reach, engagement, saves.
- **Tần suất đồng bộ**: theo tuổi bài — bài mới (< 48h) đồng bộ dày (VD mỗi giờ), bài cũ thưa dần (hàng ngày). Giảm tải và tránh gọi API quá mức.
- `updated_at` cho biết lần đồng bộ gần nhất; hiển thị "cập nhật lúc…".

### 1.4. Thao tác
| Thao tác | Điều kiện | Kết quả |
|----------|-----------|---------|
| Xem trên FB | Có `fb_post_id` | Mở link bài thật |
| Đăng lại | `status ∈ {failed, published}` | Tạo post mới (draft/scheduled) từ nội dung cũ; **không** ghi đè bài cũ |
| Chỉnh sửa | Tùy chính sách — sửa bài đã đăng thật cần gọi API update (nếu hỗ trợ) hoặc chỉ sửa bản ghi nội bộ | |
| Xóa | Không xóa khi `processing` | `status = deleted`; tùy chọn xóa cả bài trên FB nếu người dùng chọn |
| Xuất dữ liệu | — | Export CSV/Excel danh sách + metrics theo bộ lọc |

## 2. Luồng đồng bộ metrics

```
Cron đồng bộ metrics (theo bucket tuổi bài)
        │
        ▼
Lấy danh sách posts published cần refresh
        │
        ├── channel = graph_api → GET /{fb_post_id}/insights
        └── channel = browser   → mở profile, đọc số liệu bài
        │
        ▼
Upsert post_metrics (likes, comments, shares, reach, engagement, saves)
        │
        ▼
Cập nhật updated_at + activity_logs (nếu có thay đổi lớn)
```

## 3. Ngoại lệ / biên
- Bài `failed` không có metrics → khối Hiệu suất hiển thị trạng thái lỗi + lý do từ execution log.
- API Insights lỗi/không đủ quyền → giữ số liệu cũ, gắn nhãn "đồng bộ thất bại".
- Xuất dữ liệu tập lớn → chạy nền, gửi thông báo khi có file tải về.
- "Đăng lại" một bài `published` → cảnh báo tránh trùng nội dung trên cùng fanpage.
