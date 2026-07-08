# Tạo bài viết — Soạn nội dung & lên lịch

> Màn thiết kế: [`Design/createContent.png`](../../Design/createContent.png)

## Mục đích
Soạn nội dung bài viết (text / ảnh / video / link / poll), chọn nơi đăng, thiết lập lịch và các tùy chọn nâng cao, sau đó **Lưu nháp** hoặc **Lên lịch**.

## Thành phần giao diện

| Khối | Nội dung |
|------|----------|
| **1. Chọn nơi đăng** | Chọn Fanpage + Chrome profile sẽ dùng; có thể thêm nhiều fanpage |
| **2. Nội dung** | Tab Văn bản / Ảnh / Video / Link / Poll · đếm ký tự · emoji, hashtag, mention, biến `{..}` (spintax/placeholder) · nút **Gợi ý AI** · upload tối đa **10 ảnh hoặc 1 video** (kéo-thả) |
| **3. Thiết lập đăng** | Lên lịch / Đăng ngay · ngày giờ · quy tắc lặp lại |
| **4. Xem trước** | Preview giống giao diện Facebook thật |
| **5. Thiết lập nâng cao** | Trình duyệt sẽ dùng · tự rút gọn link · tắt thông báo bình luận · tự chia sẻ · ghi chú (≤ 200 ký tự) |
| **6. Thông tin kiểm tra** | Trạng thái cookie · "Khả năng đăng: Có thể đăng" · nút **Kiểm tra lại** |
| **Hành động** | **Lưu nháp** / **Lên lịch** |

## Bảng dữ liệu liên quan
- `posts` — bản ghi bài viết (kèm `options` jsonb, `channel` graph_api/browser)
- `post_media` — ảnh/video đính kèm
- `fanpages` — nơi đăng, kiểm tra `can_post`, `api_enabled`
- `cookies` — validate phiên trước khi lưu (nhánh browser)
- `browser_profiles` — profile thực thi (nhánh browser fallback)
- `ai_agents` — sinh gợi ý nội dung
- `settings` — giá trị mặc định (fanpage, loại nội dung, giờ đăng…)

## Tài liệu liên quan
- [Nghiệp vụ](./NGHIEP_VU.md)
- [Các hàm xử lý luồng nghiệp vụ](./HAM_XU_LY.md)
