# Bài viết — Các hàm xử lý luồng nghiệp vụ

> Đặc tả service + worker layer (chưa có code — thiết kế hàm đề xuất).

## PublishedPostService

### `getPostStats(userId, filter): PostStats`
Đếm posts theo trạng thái đã thực thi: total, published, failed, processing, deleted.

### `listPosts(userId, filter, page): PostRow[]`
Join `posts` + `fanpages` + `browser_profiles` + `post_metrics` (like/comment), order by `published_at desc`.

### `getPostDetail(postId): PostDetail`
Trả về post + `getExecutionTimeline(postId)` (tab Timeline) + `getMetrics(postId)` (tab Tương tác + khối Hiệu suất).

### `getMetrics(postId): PostMetrics`
Đọc `post_metrics`: likes, comments, shares, reach, engagement, saves, updatedAt.

### `republishPost(postId, dto): Post`
Tạo post mới từ nội dung cũ (`status = draft` hoặc `scheduled`), không đụng bài gốc; cảnh báo nếu trùng fanpage.

### `deletePost(postId, alsoDeleteOnFacebook = false)`
```
1. Chặn nếu status = processing
2. status = deleted
3. Nếu alsoDeleteOnFacebook && fb_post_id:
     channel api → DELETE /{fb_post_id}
     channel browser → mở profile, xóa bài qua UI
4. Ghi activity_logs
```

### `exportPosts(userId, filter, format): FileRef`
```
1. Query posts + metrics theo filter (không phân trang)
2. Sinh CSV/XLSX (bài viết, fanpage, trạng thái, thời gian, metrics)
3. Nếu tập lớn → chạy nền, trả jobId, thông báo khi xong
```

## MetricsSyncService (worker)

### `onPostPublished(postId)` *(event handler)*
Nghe event `post.published` do PostExecutor phát → đăng ký bài vào lịch đồng bộ dày (bucket < 48h) và chạy 1 lần sync sớm, thay vì chờ cron quét. Xem [LichDang/HAM_XU_LY.md](../LichDang/HAM_XU_LY.md) (`executeJob` bước emit).

### `syncMetricsCron()` *(cron)*
```
1. Chia posts published theo bucket tuổi:
     < 48h → sync mỗi giờ;  < 7 ngày → mỗi 6h;  cũ hơn → mỗi ngày
2. Với mỗi post đến hạn → syncPostMetrics(post)
```

### `syncPostMetrics(post)`
```
1. channel = graph_api → GET /{fb_post_id}/insights?metric=post_impressions,...
   channel = browser   → mở profile, đọc số liệu từ bài
2. Chuẩn hóa → upsert post_metrics
3. Set updated_at; nếu lỗi → giữ số cũ, đánh dấu sync_failed
```

## Phụ thuộc
- `getExecutionTimeline()` — dùng chung, xem [LichDang/HAM_XU_LY.md](../LichDang/HAM_XU_LY.md)
- Đăng lại đi qua `schedulePost()` — xem [TaoBaiViet/HAM_XU_LY.md](../TaoBaiViet/HAM_XU_LY.md)
