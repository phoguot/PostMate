# Dashboard — Nghiệp vụ

## 1. Quy tắc nghiệp vụ

### 1.1. Bộ lọc thời gian
- Người dùng chọn khoảng ngày (from – to); mặc định 30 ngày gần nhất.
- Mọi KPI, biểu đồ đều tính trong khoảng thời gian này.
- **So sánh kỳ trước**: kỳ trước = khoảng thời gian liền kề có cùng độ dài (VD: chọn 23/05–23/06 thì kỳ trước là 22/04–22/05). Hiển thị % tăng/giảm cho mỗi KPI.

### 1.2. Định nghĩa KPI
| KPI | Điều kiện tính |
|-----|----------------|
| Tổng bài viết | `posts` có `created_at` trong kỳ, mọi trạng thái trừ `deleted` |
| Đã đăng thành công | `status = published` — kèm tỷ lệ % = published / tổng |
| Chờ đăng | `status = scheduled` và `scheduled_at` chưa tới |
| Đã lỗi | `status = failed` (đã hết lượt retry) |
| Đang xử lý | `status = processing` (job đang chạy trong queue/browser) |

### 1.3. Trạng thái hệ thống (health)
| Thành phần | Quy tắc đánh giá |
|-----------|------------------|
| AI Agent | Đếm agent `status = active`; cảnh báo nếu = 0 |
| Chrome Instances | `running / tổng`; cảnh báo nếu tỷ lệ chạy < 50% |
| Cookie | `valid / tổng`; cảnh báo khi có cookie `expiring` hoặc `invalid` |
| Queue | Số job đang đợi; "Ổn định" nếu < ngưỡng cấu hình, "Quá tải" nếu vượt |
| Hệ thống | Tổng hợp: **Tốt** nếu không thành phần nào cảnh báo; ngược lại **Cần chú ý** |

### 1.4. Tự động cập nhật
- Polling hoặc WebSocket mỗi **30 giây** cho: KPI, trạng thái hệ thống, nhật ký hoạt động.
- Biểu đồ và bảng chỉ refresh khi người dùng đổi bộ lọc hoặc theo chu kỳ 30s.

## 2. Luồng nghiệp vụ chính

```
Người dùng mở Dashboard
        │
        ▼
Load bộ lọc thời gian mặc định (30 ngày)
        │
        ▼
Gọi song song: KPI · Chart · Donut · Recent posts · Top fanpages · System health · Activity logs
        │
        ▼
Render → đặt timer 30s → refresh KPI + health + logs
        │
        ▼
Người dùng đổi khoảng ngày → gọi lại toàn bộ với from/to mới
```

## 3. Điều hướng từ Dashboard
- Click 1 dòng "Bài viết gần đây" → mở chi tiết bài trong màn **Lịch đăng** hoặc **Bài viết** (tùy trạng thái).
- Click thẻ KPI → mở màn tương ứng với filter trạng thái đã áp sẵn.
- Click 1 fanpage trong "Top fanpage" → mở chi tiết ở màn **Fanpage**.

## 4. Ngoại lệ / biên
- Kỳ không có dữ liệu → KPI hiển thị 0, biểu đồ rỗng có empty-state, không lỗi.
- Queue service không phản hồi → khối Queue hiển thị "Không xác định" thay vì chặn cả trang.
- So sánh kỳ trước khi kỳ trước = 0 bài → hiển thị "—" thay vì % vô nghĩa (chia 0).
