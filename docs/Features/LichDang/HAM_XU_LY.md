# Lịch đăng — Các hàm xử lý luồng nghiệp vụ

> Đặc tả service + worker layer (chưa có code — thiết kế hàm đề xuất).

## ScheduleService (đọc/quản lý danh sách)

### `getScheduleStats(userId, filter): ScheduleStats`
Đếm posts theo trạng thái trong bộ lọc: total, published, scheduled, failed, expired.

### `listScheduledPosts(userId, filter, page): PostRow[]`
Filter: status, fanpageId, browserProfileId, dateFrom/dateTo. Join `posts` + `fanpages` + `browser_profiles`, kèm `attempt_count/max_attempts`.

### `getPostDetail(postId): PostDetail`
Trả về thông tin post + `getExecutionTimeline(postId)`.

### `getExecutionTimeline(postId): ExecutionStep[]`
Đọc `post_execution_logs` order by `logged_at`: `[{ step, status, durationSec, loggedAt }]`.

### `duplicatePost(postId): Post`
Copy post + media → `status = draft`, xóa `fb_post_id`, reset attempt.

### `updateScheduledPost(postId, dto): Post`
Nếu đang `scheduled` → `QueueService.cancelJob(postId)` rồi enqueue lại theo `scheduled_at` mới.

### `deletePost(postId)`
Chặn nếu `status = processing`; ngược lại `status = deleted` + `cancelJob`.

## QueueService (hàng đợi)

### `enqueueJob(postId, runAt)`
Đưa job vào queue với thời điểm chạy = `runAt`. Trả về `jobId`.

### `cancelJob(postId)`
Gỡ job khỏi queue nếu chưa chạy.

### `cancelAccountJobs(accountId)` / `cancelFanpageJobs(fanpageId)`
Hủy hàng loạt job chưa chạy của một tài khoản/fanpage — gọi khi checkpoint hoặc gỡ liên kết. Chỉ hủy job `scheduled` (không đụng `processing`).

### `getPendingCount(): number`
Số job đang chờ — dùng cho Dashboard health.

### `expireStaleJobs()` *(cron mỗi phút)*
```
1. Tìm posts status = scheduled, scheduled_at < now - threshold, CHƯA bị claim
   (chỉ status = 'scheduled', không đụng job đang 'processing')
2. Set status = expired, ghi activity_logs
```

## PostExecutor (worker)

### `executeJob(job)`  — entrypoint worker
```
1. claim = UPDATE posts SET status='processing'
             WHERE id=job.postId AND status='scheduled'   -- flip nguyên tử
   Nếu claim không đổi dòng nào → job đã bị worker khác giữ → return
2. post = load(job.postId)
3. preflight = precheckByChannel(post)      → fail → handleFailure()
4. Nếu isAlreadyPublished(post) → set published + fb_post_id, return  -- idempotency
5. content = resolveSpintax(post.content); content = shortenLinks(content)
6. try:
     result = post.channel == 'graph_api'
                ? publishViaApi(post, content, idempotencyKey(post))
                : publishViaBrowser(post, content)
     verifyPublished(post, result)          -- xác nhận bài thật sự lên
     set fb_post_id=result.fbPostId, status=published, log steps, activity_logs
     emit('post.published', post.id)        -- kích hoạt đồng bộ metrics
   catch error:
     handleFailure(post, error)
```

### `precheckByChannel(post): PreflightResult`
```
Chung: fanpage.can_post == true, rate limit theo account chưa vượt (vượt → hoãn, KHÔNG tốn attempt)
channel = graph_api:
   token còn hạn? không → thử TokenService.refresh(); refresh fail → permanent error
channel = browser:
   cookie.status == valid && profile != offline (offline → thử profile khác / lỗi tạm thời)
```

### `isAlreadyPublished(post): bool`  — chống đăng trùng
```
1. Nếu post.fb_post_id đã có → true
2. channel = browser → query bài gần nhất trên page, so khớp idempotencyKey/nội dung/thời điểm
3. channel = graph_api → tra dedup theo idempotencyKey (nếu API hỗ trợ)
```

### `publishViaApi(post, content, idemKey): {fbPostId}`
```
1. token = fanpage.page_access_token
2. Nếu có media → POST /{page-id}/photos (published=false) → media_fbid[]
3. POST /{page-id}/feed { message, attached_media | link }  (kèm idemKey nếu hỗ trợ)
   Lưu ý: KHÔNG truyền scheduled_publish_time nếu queue đã giữ giờ (chọn 1 cơ chế lịch)
4. Nếu /feed lỗi sau khi đã upload media → cleanupOrphanMedia(media_fbid)
5. Ghi execution_logs rút gọn; trả fb_post_id
```

### `publishViaBrowser(post, content): {fbPostId}`
```
Với mỗi bước, ghi post_execution_logs + delay(jitter):
1. Mở Chrome profile (headless, proxy, fingerprint)
2. Nạp cookie → truy cập Facebook
3. Điều hướng tới fanpage (qua UI thật)
4. Điền nội dung (gõ theo nhịp người)
5. Upload media
6. Click "Đăng" → chờ xác nhận
7. Trích xuất fb_post_id
Bất kỳ bước lỗi → ném error kèm step name
```

### `verifyPublished(post, result)`
Xác nhận bài thật sự lên (browser: composer đóng + bài xuất hiện + có fb_post_id; API: response có id). Không xác nhận được → ném lỗi tạm thời, KHÔNG đánh dấu published.

### `handleFailure(post, error)`
```
1. classify(error):
   - permanent  (vi phạm nội dung, page gỡ, token thu hồi) → status=failed, log
   - rate_limit (HTTP 429 / FB báo tần suất)               → re-enqueue backoff dài,
                                                              KHÔNG tăng attempt_count
   - checkpoint → account.status=checkpoint,
                  QueueService.cancelAccountJobs(accountId), cảnh báo màn Tài khoản FB
   - transient  → attempt_count++;
                  attempt < max → re-enqueue backoff = base * 2^attempt (+ jitter)
                  attempt = max → status=failed
2. Ghi execution_logs + activity_logs (level=error)
```

### `idempotencyKey(post): string`
Sinh key ổn định cho một lần đăng, VD `post.id + ':' + post.attempt_count`. Dùng cho `isAlreadyPublished` và dedup API.

## Cron / scheduler
- `expireStaleJobs()` — mỗi phút.
- `recurringScheduler()` — quét `repeat_rule`, sinh post mới cho lần lặp kế tiếp.

## Phụ thuộc
- `validatePostability()`, `resolveSpintax()`, `shortenLinks()` — xem [TaoBaiViet/HAM_XU_LY.md](../TaoBaiViet/HAM_XU_LY.md)
- Metrics đồng bộ sau khi published — xem [BaiViet/HAM_XU_LY.md](../BaiViet/HAM_XU_LY.md)
