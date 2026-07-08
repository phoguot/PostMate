# Tạo bài viết — Các hàm xử lý luồng nghiệp vụ

> Đặc tả service layer (chưa có code — đây là thiết kế hàm đề xuất).

## PostComposerService

### `getComposerDefaults(userId): ComposerDefaults`
Nạp giá trị mặc định từ `settings`: `default_fanpage_id`, `default_content_type`, `default_status`, `default_post_time`, các toggle (`auto_shorten_link`, `show_ai_suggestions`, `confirm_before_post`).

### `listPostableTargets(userId): TargetOption[]`
```
1. Query fanpages của user, join facebook_accounts + browser_profiles + cookies
2. Với mỗi page xác định: channel = api_enabled ? 'graph_api' : 'browser'
3. Trả về [{ fanpageId, name, avatar, channel, canPost, reason? }]
```

### `validatePostability(fanpageId, browserProfileId?): PostabilityResult`
Hàm sau nút **"Kiểm tra lại"**:
```
1. Load fanpage → check status = active, can_post = true
2. Nếu channel = graph_api:
     check page_access_token tồn tại && token_expires_at > now
3. Nếu channel = browser:
     check cookie.status = valid && profile.status != offline
4. Trả về { canPost: bool, channel, checks: [{ name, passed, message }] }
```

### `uploadMedia(userId, files[]): PostMediaDraft[]`
```
1. Validate: đếm ảnh ≤ 10, video ≤ 1, không trộn video + ảnh
2. Validate mime/size từng file
3. Lưu storage → trả về [{ tempId, type, url, orderIndex }]
4. Cập nhật storage_used trong settings
```

### `generateAiSuggestion(userId, prompt, contentType): AiSuggestion`
```
1. Chọn ai_agent status = active
2. Gửi prompt (chủ đề + nội dung hiện có + loại content)
3. Trả về { caption, hashtags[], variants[] }
4. Ghi activity_logs type = 'ai_suggestion'
```

### `saveDraft(userId, dto: ComposePostDto): Post[]`
```
1. Validate tối thiểu (có fanpage hoặc có nội dung)
2. Với mỗi fanpage đã chọn → tạo/ cập nhật post status = draft
3. Ghi post_media theo orderIndex
4. Trả về danh sách post đã lưu
```

### `schedulePost(userId, dto: ComposePostDto): Post[]`
```
1. validateContent(dto)            — ràng buộc theo content_type
2. validateSchedule(dto)           — scheduled_at > now (theo settings.timezone)
3. Với mỗi fanpage: validatePostability() — fail page nào trả lỗi page đó
4. Tạo posts: status = 'scheduled', channel theo page, options jsonb,
   attempt_count = 0, max_attempts = 3
5. Ghi post_media
6. enqueueJob(postId, scheduled_at)         → QueueService
7. Nếu repeat_rule ≠ none → đăng ký recurring rule
8. Ghi activity_logs ("Tạo lịch — <fanpage>")
```

### `publishNow(userId, dto): Post[]`
Như `schedulePost` nhưng `scheduled_at = now()` → job chạy ngay.

## Hàm hỗ trợ nội bộ

### `validateContent(dto)`
Kiểm tra ràng buộc từng loại: text bắt buộc khi không có media; ảnh ≤ 10; video = 1 và không kèm ảnh; poll ≥ 2 lựa chọn; note ≤ 200 ký tự.

### `resolveSpintax(content, context): string`
Resolve biến `{..}` tại **thời điểm đăng** (gọi từ worker, không phải lúc lưu) — mỗi fanpage nhận biến thể riêng.

### `shortenLinks(content): string`
Nếu `options.auto_shorten_link` → thay URL bằng link rút gọn (gọi từ worker trước khi đăng).

## Phụ thuộc
- `QueueService.enqueueJob()` — xem [LichDang/HAM_XU_LY.md](../LichDang/HAM_XU_LY.md)
- Worker thực thi đăng bài — xem mục PostExecutor trong [LichDang/HAM_XU_LY.md](../LichDang/HAM_XU_LY.md)
