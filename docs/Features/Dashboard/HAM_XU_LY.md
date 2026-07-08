# Dashboard — Các hàm xử lý luồng nghiệp vụ

> Đặc tả service layer (chưa có code — đây là thiết kế hàm đề xuất).

## DashboardService

### `getOverviewStats(userId, dateFrom, dateTo): OverviewStats`
Tính 5 thẻ KPI + so sánh kỳ trước.
```
1. Query posts trong [dateFrom, dateTo] group by status
2. Tính kỳ trước: prevFrom = dateFrom - (dateTo - dateFrom), prevTo = dateFrom - 1
3. Query tương tự cho kỳ trước
4. Trả về { total, published, publishedRate, scheduled, failed, processing,
            deltas: { ...% so kỳ trước, null nếu kỳ trước = 0 } }
```

### `getPostPerformanceChart(userId, dateFrom, dateTo, groupBy = 'day'): ChartSeries[]`
Dữ liệu biểu đồ cột chồng theo ngày.
```
1. Query posts group by date(scheduled_at), status ∈ {published, scheduled, failed}
2. Fill các ngày trống bằng 0
3. Trả về [{ date, published, pending, failed }]
```

### `getStatusDistribution(userId, dateFrom, dateTo): DonutData`
Phân bổ trạng thái cho donut chart — count posts group by status, quy đổi %.

### `getRecentPosts(userId, limit = 10): RecentPostRow[]`
Bảng bài viết gần đây: join `posts` + `fanpages` + `browser_profiles`, order by `updated_at desc`.

### `getTopFanpages(userId, dateFrom, dateTo, limit = 5): TopFanpageRow[]`
```
1. Join fanpages ← posts ← post_metrics trong kỳ
2. engagement = sum(likes + comments + shares)
3. Order by engagement desc, limit
```

### `getSystemHealth(userId): SystemHealth`
```
1. aiAgents   = count(ai_agents where status = 'active')
2. browsers   = { running: count(running), total: count(*) } từ browser_profiles
3. cookies    = { valid, expiring, invalid, total } từ cookies
4. queue      = queueService.getPendingCount() — bọc try/catch, lỗi → 'unknown'
5. overall    = 'good' nếu không có cảnh báo, ngược lại 'warning'
```

### `getActivityFeed(userId, cursor?, limit = 20): ActivityLogPage`
Đọc `activity_logs` order by `created_at desc`, phân trang cursor. Dùng cho stream realtime (kết hợp WebSocket push khi có log mới).

## Realtime / Scheduler

### `dashboardRefreshTicker()` *(client-side)*
Timer 30s → gọi lại `getOverviewStats`, `getSystemHealth`, `getActivityFeed` (delta theo cursor).

### `broadcastActivityLog(log)` *(server-side, gọi từ ActivityLogService)*
Mỗi khi ghi `activity_logs` → push qua WebSocket channel `dashboard:{userId}`.

## Phụ thuộc
- `QueueService.getPendingCount()` — xem [LichDang/HAM_XU_LY.md](../LichDang/HAM_XU_LY.md)
- `ActivityLogService.write()` — hàm dùng chung, mọi feature gọi khi có sự kiện đáng ghi.
