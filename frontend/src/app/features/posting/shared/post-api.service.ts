import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from '../../../core/http/api.service';
import { PaginatedResult } from '../../../core/http/api.model';
import { Post } from './post.model';

export interface PostListPayload {
  page?: number;
  pageSize?: number;
  id?: number;
  status?: number;
  statuses?: number[];
  fanpageId?: number;
  browserProfileId?: number;
  channel?: number;
  fromDate?: string;
  toDate?: string;
  keyword?: string;
  sort?: string;
  dir?: 'asc' | 'desc';
}

export interface PostStats {
  total: number;
  draft: number;
  scheduled: number;
  processing: number;
  published: number;
  failed: number;
  expired: number;
}

export interface PostSavePayload {
  id?: number;
  fanpageId?: number;
  fanpageIds?: number[];
  browserProfileId?: number;
  aiAgentId?: number;
  contentType?: number;
  title?: string;
  content?: string;
  media?: unknown[];
  scheduledAt?: string;
  repeatRule?: string;
  note?: string;
  autoShortenLink?: boolean;
  disableCommentNotif?: boolean;
  autoShare?: boolean;
}

@Injectable({ providedIn: 'root' })
export class PostApiService {
  private readonly api = inject(ApiService);

  list(payload: PostListPayload = {}): Observable<PaginatedResult<Post>> {
    return this.api.post<PaginatedResult<Post>>('/posting/post/index', payload as Record<string, unknown>);
  }

  stats(payload: Partial<PostListPayload> = {}): Observable<PostStats> {
    return this.api.post<PostStats>('/posting/post/stats', payload as Record<string, unknown>);
  }

  detail(id: number): Observable<Post> {
    return this.api.post<Post>('/posting/post/detail', { id });
  }

  saveDraft(payload: PostSavePayload): Observable<{ ids: number[] }> {
    return this.api.post<{ ids: number[] }>('/posting/post/savedraft', payload as Record<string, unknown>);
  }

  schedule(payload: PostSavePayload): Observable<{ ids: number[] }> {
    return this.api.post<{ ids: number[] }>('/posting/post/schedule', payload as Record<string, unknown>);
  }

  publishNow(payload: PostSavePayload): Observable<{ ids: number[] }> {
    return this.api.post<{ ids: number[] }>('/posting/post/publishnow', payload as Record<string, unknown>);
  }

  duplicate(id: number): Observable<{ id: number }> {
    return this.api.post<{ id: number }>('/posting/post/duplicate', { id });
  }

  updateStatus(id: number, status: number): Observable<{ status: number }> {
    return this.api.post<{ status: number }>('/posting/post/updatestatus', { id, status });
  }

  delete(id: number): Observable<unknown> {
    return this.api.post('/posting/post/delete', { id });
  }
}
