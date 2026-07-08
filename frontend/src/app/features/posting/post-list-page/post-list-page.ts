import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { format } from 'date-fns';
import { LucideFileText, LucideCircleCheck, LucideRefreshCw, LucideAlertTriangle, LucideCircleOff, LucideX } from '@lucide/angular';
import { PageHeader } from '../../../shared/ui/page-header/page-header';
import { StatCard } from '../../../shared/ui/stat-card/stat-card';
import { StatusBadge } from '../../../shared/ui/status-badge/status-badge';
import { Pagination } from '../../../shared/ui/pagination/pagination';
import { DetailDrawer } from '../../../shared/ui/drawer/detail-drawer';
import { PostApiService, PostStats } from '../shared/post-api.service';
import { Post } from '../shared/post.model';
import { POST_CONTENT_TYPE_META, POST_STATUS_META, PostStatus } from '../../../core/models/status.model';
import { FanpageApiService } from '../../facebook/shared/facebook-api.service';
import { BrowserProfileApiService } from '../../infra/shared/browser-profile-api.service';
import { Fanpage } from '../../facebook/shared/facebook.model';
import { BrowserProfile } from '../../infra/shared/browser-profile.model';
import { NotifyService } from '../../../core/notify/notify.service';
import { ApiError } from '../../../core/http/api.model';
import { Paginator } from '../../../core/http/api.model';

@Component({
  selector: 'app-post-list-page',
  standalone: true,
  imports: [PageHeader, StatCard, StatusBadge, Pagination, DetailDrawer, FormsModule],
  templateUrl: './post-list-page.html'
})
export class PostListPage implements OnInit {
  private readonly postApi = inject(PostApiService);
  private readonly fanpageApi = inject(FanpageApiService);
  private readonly browserApi = inject(BrowserProfileApiService);
  private readonly notify = inject(NotifyService);

  protected readonly fileTextIcon = LucideFileText;
  protected readonly checkIcon = LucideCircleCheck;
  protected readonly processingIcon = LucideRefreshCw;
  protected readonly alertIcon = LucideAlertTriangle;
  protected readonly expiredIcon = LucideCircleOff;
  protected readonly closeIcon = LucideX;
  protected readonly postStatusMeta = POST_STATUS_META;
  protected readonly postContentTypeMeta = POST_CONTENT_TYPE_META;
  protected readonly statusOptions = Object.entries(POST_STATUS_META).map(([value, meta]) => ({
    value: Number(value),
    label: meta.label
  }));
  protected readonly PostStatus = PostStatus;

  protected readonly loading = signal(true);
  protected readonly posts = signal<Post[]>([]);
  protected readonly stats = signal<PostStats | null>(null);
  protected readonly paginator = signal<Paginator>({ page: 1, pageSize: 8, totalItems: 0, totalPages: 0 });
  protected readonly fanpages = signal<Fanpage[]>([]);
  protected readonly browsers = signal<BrowserProfile[]>([]);

  protected readonly keyword = signal('');
  protected readonly statusFilter = signal<number | ''>('');
  protected readonly fanpageFilter = signal<number | ''>('');
  protected readonly browserFilter = signal<number | ''>('');

  protected readonly selectedPost = signal<Post | null>(null);
  protected readonly activeTab = signal<'info' | 'timeline' | 'engagement'>('info');

  ngOnInit(): void {
    this.fanpageApi.list({ pageSize: 100 }).subscribe((res) => this.fanpages.set(res.result));
    this.browserApi.list({ pageSize: 100 }).subscribe((res) => this.browsers.set(res.result));
    this.loadStats();
    this.loadPosts(1);
  }

  protected applyFilters(): void {
    this.loadPosts(1);
    this.loadStats();
  }

  protected onPageChange(page: number): void {
    this.loadPosts(page);
  }

  protected openDetail(post: Post): void {
    this.selectedPost.set(post);
    this.activeTab.set('info');
  }

  protected closeDetail(): void {
    this.selectedPost.set(null);
  }

  protected deletePost(post: Post): void {
    if (!confirm(`Xóa bài viết "${post.title ?? '(không tiêu đề)'}"?`)) {
      return;
    }
    this.postApi.delete(post.id).subscribe({
      next: () => {
        this.notify.success('Đã xóa bài viết.');
        this.closeDetail();
        this.loadPosts(this.paginator().page);
        this.loadStats();
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Xóa bài viết thất bại.')
    });
  }

  protected duplicatePost(post: Post): void {
    this.postApi.duplicate(post.id).subscribe({
      next: () => {
        this.notify.success('Đã tạo bản sao (nháp) từ bài viết này.');
        this.loadPosts(this.paginator().page);
        this.loadStats();
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể nhân bản bài viết.')
    });
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return '-';
    }
    return format(new Date(value.replace(' ', 'T')), 'dd/MM/yyyy HH:mm');
  }

  private loadStats(): void {
    this.postApi.stats(this.currentFilterPayload()).subscribe((stats) => this.stats.set(stats));
  }

  private loadPosts(page: number): void {
    this.loading.set(true);
    this.postApi
      .list({ ...this.currentFilterPayload(), page, pageSize: this.paginator().pageSize })
      .subscribe({
        next: (res) => {
          this.posts.set(res.result);
          this.paginator.set(res.paginator);
          this.loading.set(false);
        },
        error: (err: unknown) => {
          this.loading.set(false);
          this.notify.error(err instanceof ApiError ? err.message : 'Không tải được danh sách bài viết.');
        }
      });
  }

  private currentFilterPayload() {
    return {
      keyword: this.keyword() || undefined,
      status: this.statusFilter() === '' ? undefined : Number(this.statusFilter()),
      fanpageId: this.fanpageFilter() === '' ? undefined : Number(this.fanpageFilter()),
      browserProfileId: this.browserFilter() === '' ? undefined : Number(this.browserFilter())
    };
  }
}
