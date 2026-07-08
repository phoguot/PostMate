export type StatusTone = 'success' | 'warning' | 'danger' | 'info' | 'neutral';

export interface StatusMeta {
  label: string;
  tone: StatusTone;
}

export const PostStatus = {
  DRAFT: 1,
  SCHEDULED: 2,
  PROCESSING: 3,
  PUBLISHED: 4,
  FAILED: 5,
  EXPIRED: 6,
  DELETED: 7
} as const;

export const POST_STATUS_META: Record<number, StatusMeta> = {
  [PostStatus.DRAFT]: { label: 'Nháp', tone: 'neutral' },
  [PostStatus.SCHEDULED]: { label: 'Chờ đăng', tone: 'warning' },
  [PostStatus.PROCESSING]: { label: 'Đang đăng', tone: 'info' },
  [PostStatus.PUBLISHED]: { label: 'Đã đăng', tone: 'success' },
  [PostStatus.FAILED]: { label: 'Đã lỗi', tone: 'danger' },
  [PostStatus.EXPIRED]: { label: 'Hết hạn', tone: 'neutral' },
  [PostStatus.DELETED]: { label: 'Đã xóa', tone: 'neutral' }
};

export const PostContentType = {
  TEXT: 1,
  IMAGE: 2,
  VIDEO: 3,
  LINK: 4,
  POLL: 5
} as const;

export const POST_CONTENT_TYPE_META: Record<number, StatusMeta> = {
  [PostContentType.TEXT]: { label: 'Văn bản', tone: 'neutral' },
  [PostContentType.IMAGE]: { label: 'Ảnh', tone: 'info' },
  [PostContentType.VIDEO]: { label: 'Video', tone: 'info' },
  [PostContentType.LINK]: { label: 'Link', tone: 'info' },
  [PostContentType.POLL]: { label: 'Poll (khảo sát)', tone: 'info' }
};

export const PostChannel = {
  GRAPH_API: 1,
  BROWSER: 2
} as const;

export const FacebookAccountStatus = {
  ACTIVE: 1,
  INACTIVE: 2,
  CHECKPOINT: 3
} as const;

export const FACEBOOK_ACCOUNT_STATUS_META: Record<number, StatusMeta> = {
  [FacebookAccountStatus.ACTIVE]: { label: 'Đang hoạt động', tone: 'success' },
  [FacebookAccountStatus.INACTIVE]: { label: 'Không hoạt động', tone: 'neutral' },
  [FacebookAccountStatus.CHECKPOINT]: { label: 'Gặp vấn đề', tone: 'danger' }
};

export const FanpageStatus = {
  ACTIVE: 1,
  NEED_RELOGIN: 2,
  INACTIVE: 3
} as const;

export const FANPAGE_STATUS_META: Record<number, StatusMeta> = {
  [FanpageStatus.ACTIVE]: { label: 'Đang hoạt động', tone: 'success' },
  [FanpageStatus.NEED_RELOGIN]: { label: 'Cần đăng nhập lại', tone: 'warning' },
  [FanpageStatus.INACTIVE]: { label: 'Không hoạt động', tone: 'neutral' }
};

export const CookieStatus = {
  VALID: 1,
  EXPIRING: 2,
  INVALID: 3
} as const;

export const COOKIE_STATUS_META: Record<number, StatusMeta> = {
  [CookieStatus.VALID]: { label: 'Hợp lệ', tone: 'success' },
  [CookieStatus.EXPIRING]: { label: 'Sắp hết hạn', tone: 'warning' },
  [CookieStatus.INVALID]: { label: 'Không hợp lệ', tone: 'danger' }
};

export const BrowserProfileStatus = {
  RUNNING: 1,
  STOPPED: 2,
  OFFLINE: 3
} as const;

export const BROWSER_PROFILE_STATUS_META: Record<number, StatusMeta> = {
  [BrowserProfileStatus.RUNNING]: { label: 'Đang chạy', tone: 'success' },
  [BrowserProfileStatus.STOPPED]: { label: 'Đang dừng', tone: 'danger' },
  [BrowserProfileStatus.OFFLINE]: { label: 'Ngoại tuyến', tone: 'neutral' }
};
