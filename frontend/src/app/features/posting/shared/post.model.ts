export interface PostRef {
  id: number;
  name: string;
}

export interface PostOptions {
  autoShortenLink: boolean;
  disableCommentNotif: boolean;
  autoShare: boolean;
}

export interface PostMetrics {
  likes?: number;
  comments?: number;
  shares?: number;
  reach?: number;
  engagement?: number;
  saved?: number;
}

export interface Post {
  id: number;
  title: string | null;
  content: string | null;
  contentType: number;
  fanpage: PostRef | null;
  browserProfile: PostRef | null;
  aiAgentId: number | null;
  status: number;
  channel: number;
  scheduledAt: string | null;
  publishedAt: string | null;
  attemptCount: number;
  maxAttempts: number;
  repeatRule: string | null;
  fbPostId: string | null;
  note: string | null;
  modifiedAt: number | null;
  createdAt: number | null;
  options: PostOptions;
  media: unknown[];
  metrics: PostMetrics | null;
  timeline: unknown[];
}
