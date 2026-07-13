# Luồng xử lý đăng bài

Tài liệu này mô tả luồng đăng bài đang chạy trong backend PostMate hiện tại:

- Tạo/lập lịch bài viết từ API `Posting\PostController`.
- Chọn cách phát hành: Facebook Graph native schedule hoặc queue nội bộ.
- Xử lý queue bằng HTTP cron `/api/cron/posting/run`.
- Thực thi đăng bài trong `PostExecutor`.
- Ghi lỗi vào DB để FE/API hiển thị được lý do thất bại.

## 1. Bảng và trạng thái liên quan

### `posts.status`

| Giá trị | Tên | Ý nghĩa |
|---:|---|---|
| 1 | draft | Bản nháp, chưa vào queue. |
| 2 | scheduled | Đã lên lịch, chờ Facebook publish hoặc chờ queue xử lý. |
| 3 | processing | Worker/cron đã claim và đang xử lý. |
| 4 | published | Đăng thành công. |
| 5 | failed | Đăng thất bại sau khi xác định lỗi cuối/permanent. |
| 6 | expired | Quá hạn lịch mà không còn job pending hợp lệ. |
| 7 | deleted | Đã xóa mềm. |

### `post_jobs.status`

| Giá trị | Tên | Ý nghĩa |
|---:|---|---|
| 1 | pending | Job chờ tới giờ chạy. |
| 2 | processing | Job đã được claim. |
| 3 | done | Job đã xử lý xong. |
| 4 | canceled | Job bị hủy do sửa/xóa/hủy lịch. |
| 5 | failed | Job lỗi vĩnh viễn. |

### Bảng ghi lỗi

| Bảng | Cột quan trọng | Mục đích |
|---|---|---|
| `post_jobs` | `lastError` | Lỗi gắn với job gần nhất, hữu ích khi trace queue. |
| `post_execution_logs` | `step`, `status`, `loggedAt` | Timeline xử lý từng bài, status `2` là failed. |
| `posts` | `status`, `attemptCount`, `fbPostId` | Trạng thái tổng của bài viết. |
| `activity_logs` | `type`, `message`, `level` | Nhật ký người dùng/hệ thống. |

API post response có thêm:

```json
{
  "lastError": "Nội dung lỗi mới nhất",
  "lastErrorAt": "2026-07-13 10:30:00",
  "timeline": []
}
```

`lastError` lấy từ failed log mới nhất trong `post_execution_logs`.

## 2. Luồng tạo bài / lên lịch

Entry point:

- `POST /api/posting/post/savedraft`
- `POST /api/posting/post/schedule`
- `POST /api/posting/post/publishnow`

Service chính: `Posting\Service\PostService`.

### 2.1. Validate input

`PostService::persistPosts()` xử lý:

1. Kiểm tra user đăng nhập.
2. Validate form bằng filter.
3. Chuẩn hóa `contentType`, `targetType`, `scheduledAt`, `media`.
4. Nếu `schedulePost` thì `scheduledAt` bắt buộc lớn hơn thời điểm hiện tại.
5. Nếu `publishNow` thì `scheduledAt = now`.

### 2.2. Chọn đích đăng

`targetType`:

- `TARGET_FANPAGE = 1`: đăng lên fanpage.
- `TARGET_PROFILE = 2`: đăng lên trang cá nhân.

Nếu tạo mới nhiều đích đăng, mỗi fanpage/account sẽ tạo một dòng `posts` riêng.

### 2.3. Chọn channel

`PostService::resolveChannel()`:

1. Nếu request gửi `channel` thì ưu tiên giá trị người dùng chọn.
2. Nếu đăng profile thì luôn dùng browser automation vì Graph API không publish lên profile timeline.
3. Nếu đăng fanpage:
   - Fanpage có `apiEnabled = true` thì dùng `CHANNEL_GRAPH_API`.
   - Ngược lại dùng `CHANNEL_BROWSER`.

### 2.4. Kiểm tra khả năng đăng

`PostService::validatePostability()`:

- Profile: load `facebook_accounts`, gọi `FacebookAccountService::computeCanPost()`.
- Fanpage: load `fanpages`, gọi `FanpageService::computeCanPost()`.

Nếu một đích đăng không đủ điều kiện, backend bỏ qua đích đó và xử lý tiếp các đích còn lại.

### 2.5. Lưu post và media

`PostMapper::savePost()` ghi:

- `posts`
- `post_media`
- `options`
- `attemptCount = 0`
- `maxAttempts = 3` nếu request không truyền.

Nếu là draft thì dừng tại đây, không enqueue job.

## 3. Quyết định cách chạy bài scheduled

Sau khi lưu post scheduled, `PostService::dispatchScheduledPost()` quyết định một trong hai case.

### Case A: Graph API native schedule

Điều kiện `PostService::canUseNativeGraphSchedule()`:

- `targetType = fanpage`.
- `channel = graph_api`.
- `scheduledAt` hợp lệ.
- Thời gian hẹn nằm trong khoảng Graph cho phép: từ 10 phút đến 75 ngày.

Nếu đủ điều kiện:

1. `GraphPublisher::schedule()` gọi Graph API với:
   - `published=false`
   - `scheduled_publish_time=<timestamp>`
2. Nếu Facebook trả về `fbPostId`:
   - Lưu `posts.fbPostId`.
   - Vẫn enqueue một job tracking nội bộ vào `post_jobs`.
   - API response có `delivery = facebook_schedule`.

Ý nghĩa job tracking: đến giờ, cron claim job; nếu `posts.fbPostId` đã có, `PostExecutor` coi như Facebook đã nhận lịch và mark local `published`.

### Case B: Queue nội bộ

Dùng khi:

- Đăng profile.
- Đăng fanpage nhưng không dùng Graph API.
- Lịch nằm ngoài khoảng native schedule của Graph API.
- Graph schedule fail.

Luôn gọi:

```text
QueueService::enqueueJob(postId, scheduledAt)
```

`JobMapper::enqueue()` sẽ hủy pending job cũ của cùng `postId`, sau đó tạo job mới status `pending`.

API response có `delivery = queue`.

## 4. HTTP cron production

Route:

```text
GET|POST /api/cron/posting/run
```

Controller/service:

- `Posting\Controller\CronController::runAction()`
- `Posting\Service\CronService::runPostingCron()`

Bảo vệ bằng secret:

- Query/body: `secret=<postingSecret>`
- Hoặc header: `X-Cron-Secret: <postingSecret>`

Production example:

```bash
curl -H "X-Cron-Secret: <postingSecret>" \
  "https://postmate.infinityfree.io/api/cron/posting/run?i=1"
```

Response thành công:

```json
{
  "code": 1,
  "data": {
    "processed": 0,
    "expired": 0,
    "pending": 0,
    "errors": []
  }
}
```

Trong InfinityFree có thể gặp JS cookie challenge `__test`; browser/external cron có hỗ trợ cookie sẽ qua được, request HTTP thô có thể bị trả HTML challenge.

## 5. Drain queue

`QueueService::drainDueJobs($limit)`:

1. Giới hạn `$limit` trong khoảng `1..20`.
2. Lặp đến khi hết limit:
   - `claimNext()` lấy job due.
   - Không có job thì dừng.
   - Có job thì gọi `PostExecutor::executeJob($job)`.
3. Nếu `PostExecutor` ném exception chưa handle:
   - Ghi lỗi vào response `errors`.
   - Gọi `recordUnhandledFailure()`.
4. Sau vòng lặp:
   - Gọi `expireStaleJobs()`.
   - Đếm `pending`.

`recordUnhandledFailure()` ghi DB theo thứ tự:

1. `post_jobs.status = failed`, `post_jobs.lastError = <error>`.
2. `post_execution_logs.step = "Lỗi cron: <error>"`, `status = failed`.
3. `posts.status = failed`.

Lỗi được cắt ngắn và redact các trường nhạy cảm như token/cookie.

## 6. Thực thi job đăng bài

Service: `Posting\Service\PostExecutor`.

### 6.1. Claim job/post

`executeJob()`:

1. Lấy `postId` từ job.
2. `PostMapper::claimForProcessing(postId)` flip:

```sql
posts.status: scheduled -> processing
```

Chỉ một worker/cron claim thành công.

Nếu claim fail:

- Bài không còn scheduled hoặc worker khác đã xử lý.
- Mark job `done`.
- Kết thúc.

### 6.2. Load post và idempotency

1. Load `posts`.
2. Nếu không tìm thấy post thì mark job failed.
3. Nếu post đã có `fbPostId`:
   - Gọi `finishPublished()`.
   - Set `posts.status = published`.
   - Set `publishedAt = now`.
   - Mark job done.

Nhanh gọn: có `fbPostId` thì coi như đã có bài trên Facebook hoặc đã giao lịch native thành công.

### 6.3. Precheck

`precheck()`:

- Profile: load account và gọi `FacebookAccountService::computeCanPost()`.
- Fanpage: load fanpage và gọi `FanpageService::computeCanPost()`.

Nếu không đủ điều kiện:

```text
handleFailure(..., errorType = permanent)
```

### 6.4. Publish theo channel

`publishByChannel()`:

- `CHANNEL_GRAPH_API`: gọi `GraphPublisher::publish($post, $media)`.
- `CHANNEL_BROWSER`: gọi `BrowserAgentClient::publish($post, $context)`.

Trước khi publish ghi:

```text
post_execution_logs: "Bắt đầu xử lý"
```

Nếu publish thành công và có `fbPostId`:

1. Ghi log `"Đăng bài"` success.
2. `finishPublished()`.

Nếu publish fail:

1. Ghi log `"Đăng bài"` failed.
2. `handleFailure()` theo `errorType`.

## 7. Graph API publisher

Service: `Posting\Service\GraphPublisher`.

### 7.1. Publish ngay

`publish()` gọi `send()`:

- Text/link: `POST /{pageId}/feed`.
- Ảnh: upload từng ảnh qua `/{pageId}/photos` với `published=false`, sau đó attach vào `/feed`.
- Video: `POST /{pageId}/videos`.

Graph error được chuẩn hóa:

```php
[
  'success' => false,
  'fbPostId' => null,
  'error' => 'Message (Graph code ..., subcode ...)',
  'errorType' => 'permanent|rate_limit|transient'
]
```

### 7.2. Native schedule

`schedule()` chỉ chấp nhận lịch từ 10 phút đến 75 ngày.

Payload thêm:

- `published=false`
- `scheduled_publish_time=<unix timestamp>`

Thành công thì lưu `fbPostId` vào local post.

### 7.3. Hủy native schedule

Khi sửa/xóa/hủy bài scheduled có `fbPostId`, `PostService::cancelNativeGraphSchedule()` gọi:

```text
GraphPublisher::delete($post)
```

Nếu delete thành công:

- Xóa `posts.fbPostId`.
- Sau đó hủy job local nếu cần.

## 8. Xử lý lỗi và retry

`PostExecutor::handleFailure()` là nơi chuẩn hóa lỗi.

### 8.1. Luôn ghi timeline lỗi

Trước khi phân loại:

```text
post_execution_logs.step = "Lỗi: <error>"
post_execution_logs.status = failed
```

`<error>` được sanitize:

- Cắt khoảng trắng thừa.
- Redact `access_token`, `Authorization: Bearer`, `cookie`, `cookieBlob`, `token`.
- Giới hạn độ dài.

### 8.2. Lỗi permanent

Ví dụ:

- Token hỏng/hết quyền.
- Thiếu permission.
- Fanpage/account không tồn tại.
- Tham số Graph không hợp lệ.

Xử lý:

- `posts.status = failed`.
- `post_jobs.status = failed`.
- `post_jobs.lastError = <error>`.
- Ghi `activity_logs` level error.

### 8.3. Lỗi checkpoint

Nếu đăng profile và gặp checkpoint:

- `FacebookAccountService::markCheckpoint(accountId, error)`.
- `posts.status = failed`.
- `post_jobs.status = failed`.
- Ghi activity log error.

### 8.4. Lỗi rate limit

Ví dụ Graph code `4`, `17`, `32`, `613`.

Xử lý:

- Đưa post về `scheduled`.
- `post_jobs` về `pending` với backoff dài.
- Ghi `lastError`.
- Không tăng `attemptCount`.

### 8.5. Lỗi transient

Ví dụ:

- Lỗi mạng.
- Facebook tạm thời lỗi.
- Agent/browser trả lỗi tạm thời.

Xử lý:

1. Tăng `posts.attemptCount`.
2. Nếu `attemptCount < maxAttempts`:
   - Đưa post về `scheduled`.
   - Reschedule job theo exponential backoff.
   - Ghi `post_jobs.lastError`.
3. Nếu hết attempt:
   - `posts.status = failed`.
   - `post_jobs.status = failed`.
   - Ghi activity log error.

## 9. API hiển thị lỗi cho FE

`PostMapper::getInforPost()` attach:

- `media`
- tên fanpage/account/browser profile
- `lastError`
- `lastErrorAt`
- `timeline` khi lấy chi tiết một post

`ExecutionLogMapper::getLatestFailureMapByPostIds()` lấy failed log mới nhất theo post.

FE nên hiển thị:

- Danh sách: `lastError` ngắn gọn trên cột trạng thái/tooltip.
- Chi tiết: `timeline` để xem từng bước xử lý.

## 10. Trace lỗi production

### 10.1. Gọi cron

```bash
curl -H "X-Cron-Secret: <postingSecret>" \
  "https://postmate.infinityfree.io/api/cron/posting/run?i=1"
```

Nếu response:

```json
{"processed":0,"pending":0,"errors":[]}
```

Thì hiện không có job due để xử lý.

Nếu có `errors[]`, đó là exception ngoài `PostExecutor` và backend đã cố gắng ghi xuống DB qua `recordUnhandledFailure()`.

### 10.2. Query phpMyAdmin

Job gần nhất:

```sql
SELECT id, postId, status, lastError, runAt, lockedAt, modifiedAt
FROM post_jobs
ORDER BY id DESC
LIMIT 20;
```

Timeline lỗi:

```sql
SELECT id, postId, step, status, durationSec, loggedAt
FROM post_execution_logs
WHERE status = 2
ORDER BY id DESC
LIMIT 20;
```

Post failed:

```sql
SELECT id, title, status, scheduledAt, publishedAt, attemptCount, maxAttempts, fbPostId, modifiedAt
FROM posts
WHERE status = 5
ORDER BY modifiedAt DESC, id DESC
LIMIT 20;
```

Theo một post cụ thể:

```sql
SELECT id, step, status, durationSec, loggedAt
FROM post_execution_logs
WHERE postId = <postId>
ORDER BY id ASC;
```

## 11. Các file code liên quan

| Mục đích | File |
|---|---|
| API post CRUD/schedule | `module/Posting/src/Controller/PostController.php` |
| Business tạo/sửa/lên lịch | `module/Posting/src/Service/PostService.php` |
| HTTP cron endpoint | `module/Posting/src/Controller/CronController.php` |
| Cron validation + call queue | `module/Posting/src/Service/CronService.php` |
| Queue drain/expire/unhandled error | `module/Posting/src/Service/QueueService.php` |
| Execute job/retry/error | `module/Posting/src/Service/PostExecutor.php` |
| Graph API publish/schedule/delete | `module/Posting/src/Service/GraphPublisher.php` |
| Graph HTTP client | `module/Facebook/src/Service/GraphApiClient.php` |
| Job DB mapper | `module/Posting/src/Model/Job/JobMapper.php` |
| Execution timeline mapper | `module/Posting/src/Model/Log/ExecutionLogMapper.php` |
| Post response lastError/timeline | `module/Posting/src/Model/Post/PostMapper.php`, `PostModel.php` |

## 12. Tóm tắt ngắn gọn

```text
FE tạo/lập lịch bài
  -> PostService validate + save posts/post_media
  -> dispatchScheduledPost
       -> Graph native schedule nếu đủ điều kiện
            -> lưu fbPostId + enqueue job tracking
       -> nếu không thì enqueue post_jobs

External cron
  -> /api/cron/posting/run
  -> QueueService::drainDueJobs
  -> PostExecutor::executeJob
       -> claim post scheduled -> processing
       -> precheck
       -> publish qua Graph/browser
       -> success: posts published + job done
       -> failure: log error + retry/backoff hoặc failed

FE đọc post
  -> PostMapper attach lastError/lastErrorAt/timeline
```
