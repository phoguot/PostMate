# Lịch đăng — Nghiệp vụ

## 1. Quy tắc nghiệp vụ

### 1.1. Vòng đời trạng thái bài viết
```
draft ──▶ scheduled ──▶ processing ──▶ published
                │            │
                │            ├──▶ failed (hết lượt retry / checkpoint)
                │            └──▶ scheduled (retry với backoff, attempt+1)
                └──▶ expired (quá scheduled_at mà chưa chạy được)
```
- **Hết hạn (expired)**: quá `scheduled_at` một khoảng ngưỡng (VD 30 phút) mà job chưa được thực thi (queue kẹt, profile offline…) → không tự đăng muộn, đánh dấu để người dùng quyết định.
- **Lần thử (attempt)**: hiển thị `attempt_count / max_attempts` (mặc định 3). Thất bại → retry với backoff; chạm max → `failed`.

### 1.2. Queue & phân phối
- Job vào queue theo `scheduled_at`; Orchestrator chọn kênh:
  - `channel = graph_api` → gọi Graph API trực tiếp (mặc định, ưu tiên).
  - `channel = browser` → chọn server/profile rảnh, giãn tần suất theo tài khoản (rate limit).
- Giãn lịch giữa các bài trên **cùng fanpage/tài khoản** để tránh đăng dồn dập.
- **Claim job nguyên tử**: worker chỉ được chạy job sau khi flip trạng thái thành công (`scheduled → processing` bằng update có điều kiện / `SKIP LOCKED`). Ngăn 2 worker cùng chạy 1 job → đăng trùng.
- **Một cơ chế lịch duy nhất**: hoặc queue giữ giờ rồi gọi API đăng ngay tại thời điểm đó (`published=true`), hoặc giao lịch cho FB qua `scheduled_publish_time` (`published=false`, queue chỉ tạo 1 lần rồi thôi). **Không dùng cả hai** cho cùng một bài — tránh lên lịch hai tầng / sai giờ.

### 1.2b. Chống đăng trùng (idempotency)
- Nếu worker đăng thành công lên Facebook nhưng **crash trước khi ghi `published` + `fb_post_id`**, lần retry **không được đăng lại**.
- Mỗi lần đăng gắn một **idempotency key** (VD `post_id` + `attempt`). Trước khi đăng, worker kiểm tra:
  - Kênh API: dùng dedup token / kiểm tra bài đã tồn tại theo key.
  - Kênh browser: **query bài gần nhất trên page** xem đã có bài khớp nội dung/thời điểm chưa; nếu có → coi như đã published, chỉ lấy `fb_post_id` và thoát, không click Đăng lần nữa.

### 1.2c. Phân loại lỗi khi thất bại
Không phải lỗi nào cũng retry:
| Loại lỗi | Ví dụ | Xử lý |
|----------|-------|-------|
| **Vĩnh viễn** | Nội dung vi phạm, page bị gỡ, token bị thu hồi không refresh được | `failed` ngay, **không** retry |
| **Rate limit** | HTTP 429 / FB báo tần suất | Backoff dài, **không tốn lượt attempt** |
| **Checkpoint** | FB yêu cầu xác minh | Dừng account, hủy job còn lại của account |
| **Tạm thời** | Timeout, lỗi mạng, profile crash | `attempt < max` → re-enqueue (backoff + jitter); chạm max → `failed` |

### 1.3. Timeline thực thi
- Mỗi bước của job browser được ghi vào `post_execution_logs`: bước · trạng thái · thời lượng · thời điểm.
- Các bước chuẩn: Mở trình duyệt → Truy cập Facebook → Đi tới fanpage → Điền nội dung → Tải ảnh → Click đăng → Đăng thành công.
- Kênh Graph API ghi timeline rút gọn: Chuẩn bị nội dung → Gọi API → Nhận `fb_post_id`.
- Timeline có **delay tự nhiên + jitter** (cơ chế anti-detect, xem mục 4 của [PHAN_TICH_HE_THONG.md](../../PHAN_TICH_HE_THONG.md)).

### 1.4. Thao tác trên bài đã lên lịch
| Thao tác | Điều kiện | Kết quả |
|----------|-----------|---------|
| Chỉnh sửa | `status ∈ {draft, scheduled, failed, expired}` | Mở lại composer; nếu đang `scheduled` → hủy job cũ, enqueue lại |
| Nhân bản | Mọi trạng thái | Tạo bản sao `status = draft` |
| Xóa | Không xóa khi `processing` | `status = deleted`, hủy job trong queue |
| Xem trên Facebook | Chỉ khi có `fb_post_id` | Mở link bài thật |

## 2. Luồng thực thi job đăng bài (end-to-end)

```
Đến giờ scheduled_at → Queue lấy job
        │
        ▼
Claim nguyên tử: scheduled → processing   (claim fail → bỏ qua, worker khác giữ)
        │
        ▼
Precheck theo kênh:
   API     → token còn hạn? (không → refresh; refresh fail → failed)
   browser → cookie valid + profile online? (offline → thử profile khác / retry)
   + rate limit theo account (vượt → hoãn, KHÔNG tốn attempt)
        │
        ▼
Idempotency: bài đã có fb_post_id / đã tồn tại trên page? ── có ──▶ đánh dấu published, thoát
        │ chưa
        ▼
Đăng nội dung (resolve spintax, shorten link):
   Kênh API:     photos (unpublished) → /feed { message, attached_media | link }
   Kênh browser: nạp cookie → vào page → điền nội dung → upload media → click Đăng
        │
        ▼
Verify đã đăng thật → trích fb_post_id
        ├── Thành công: status=published, ghi fb_post_id + execution_logs,
        │               phát event đồng bộ metrics
        └── Thất bại → phân loại lỗi (mục 1.2c):
                 vĩnh viễn   → failed (không retry)
                 rate limit  → backoff dài, không tốn attempt
                 checkpoint  → dừng account, hủy job còn lại, báo màn Tài khoản FB
                 tạm thời    → attempt < max ? re-enqueue(backoff+jitter) : failed
                 (API: nếu đã upload media mà /feed lỗi → dọn media rác)
        │
        ▼
Ghi activity_logs ("Đăng bài thành công/thất bại — <fanpage>")
```

## 3. Ngoại lệ / biên
- Profile offline khi tới giờ → thử chọn profile khác cùng tài khoản (nếu có), không thì retry/expired.
- Checkpoint giữa chừng → hủy các job còn lại của tài khoản đó, đánh dấu account `checkpoint`.
- Sửa bài khi job sắp chạy (< 1 phút) → khóa sửa hoặc yêu cầu hủy lịch trước.
- Retry không áp dụng cho lỗi vĩnh viễn (nội dung vi phạm, page bị gỡ) → fail ngay, ghi rõ lý do.
- **Worker crash sau khi FB đã nhận bài** → lần retry phải nhận diện qua idempotency (mục 1.2b), không đăng lại.
- **`expireStaleJobs` chỉ được expire job chưa bị claim** (`status = scheduled`) — không đụng job đang `processing`, tránh vừa `expired` vừa `processing`.
- **Verify thất bại (browser)**: click "Đăng" xong nhưng không xác nhận được bài lên (composer báo lỗi / timeout) → coi là thất bại tạm thời, không đánh dấu published.
- **Media rác (API)**: đã upload ảnh `unpublished` nhưng `/feed` lỗi → dọn `media_fbid` mồ côi khi rollback.
