# Tạo bài viết — Nghiệp vụ

## 1. Quy tắc nghiệp vụ

### 1.1. Nơi đăng
- Phải chọn **ít nhất 1 fanpage**. Chọn nhiều fanpage → hệ thống tạo **1 post cho mỗi fanpage** (nhân bản nội dung).
- Mỗi fanpage đi kèm kênh thực thi:
  - `api_enabled = true` → kênh **Graph API** (mặc định theo kiến trúc API-first).
  - Ngược lại → kênh **browser**, phải chọn Chrome profile hợp lệ (đang chạy/khởi động được, có cookie `valid`).

### 1.2. Nội dung
| Loại | Ràng buộc |
|------|-----------|
| Văn bản | Bắt buộc có nội dung khi không có media; đếm ký tự realtime |
| Ảnh | Tối đa **10 ảnh**; định dạng jpg/png/webp; giới hạn dung lượng theo cấu hình |
| Video | Tối đa **1 video**; không được kèm ảnh |
| Link | 1 URL; tùy chọn tự rút gọn theo `options.auto_shorten_link` |
| Poll | Câu hỏi + ≥ 2 lựa chọn |
- Hỗ trợ biến `{..}` (spintax/placeholder) — được resolve tại thời điểm đăng để mỗi fanpage nhận nội dung hơi khác nhau (giảm trùng lặp).
- **Gợi ý AI**: gửi chủ đề/nội dung hiện tại cho AI Agent → nhận caption + hashtag đề xuất; người dùng có thể chèn hoặc bỏ qua.

### 1.3. Thiết lập đăng
- **Đăng ngay**: `scheduled_at = now`, đưa vào queue lập tức.
- **Lên lịch**: `scheduled_at` phải **ở tương lai**; theo múi giờ trong `settings.timezone`.
- **Lặp lại**: "Không lặp lại" hoặc quy tắc lặp (daily/weekly/cron) → mỗi lần lặp sinh 1 post mới ở thời điểm kích hoạt.

### 1.4. Kiểm tra trước khi lưu ("Kiểm tra lại")
Chạy khi bấm nút hoặc trước khi Lên lịch:
1. Fanpage: `can_post = true`, trạng thái `active`.
2. Kênh API: token còn hạn (`token_expires_at > now`).
3. Kênh browser: cookie `status = valid` và profile không `offline`.
4. Kết quả hiển thị: "Khả năng đăng: **Có thể đăng / Không thể đăng**" + lý do.
- Không đạt → chặn **Lên lịch** (vẫn cho **Lưu nháp**).

### 1.5. Lưu
| Hành động | Kết quả |
|-----------|---------|
| **Lưu nháp** | `status = draft`, không vào queue, không bắt buộc qua bước kiểm tra |
| **Lên lịch** | Validate toàn bộ → `status = scheduled` → enqueue job theo `scheduled_at` |
- Tự lưu nháp định kỳ nếu `settings.auto_save_draft = true`.
- Nếu `settings.confirm_before_post = true` → hiện dialog xác nhận trước khi lên lịch/đăng ngay.

## 2. Luồng nghiệp vụ chính

```
Mở màn Tạo bài viết
   │  (nạp mặc định từ settings: fanpage, loại nội dung, giờ đăng)
   ▼
Chọn fanpage(s) ──▶ hệ thống tự xác định kênh (API / browser) cho từng page
   ▼
Soạn nội dung (± Gợi ý AI, ± upload media)
   ▼
Thiết lập thời gian + tùy chọn nâng cao
   ▼
"Kiểm tra lại" → validate cookie/token + can_post
   │
   ├── Không đạt → hiển thị lý do → chỉ cho Lưu nháp
   ▼
Lưu nháp (draft)  hoặc  Lên lịch (scheduled → enqueue)
   ▼
Ghi activity_logs ("Tạo lịch đăng — <fanpage>")
```

## 3. Ngoại lệ / biên
- Upload media thất bại → giữ form, báo lỗi từng file, không mất nội dung đã gõ.
- `scheduled_at` trong quá khứ → báo lỗi, gợi ý "Đăng ngay".
- Chọn nhiều fanpage nhưng 1 page không thể đăng → cảnh báo riêng page đó, cho phép loại khỏi danh sách và tiếp tục.
- Vượt giới hạn 10 ảnh / 1 video → chặn ngay tại bước upload.
- Ghi chú > 200 ký tự → cắt/chặn tại UI.
