import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { format } from 'date-fns';
import { LucideFileText, LucideCircleCheck, LucideClock, LucideAlertTriangle, LucideCircleOff } from '@lucide/angular';
import { PageHeader } from '../../../shared/ui/page-header/page-header';
import { StatCard } from '../../../shared/ui/stat-card/stat-card';
import { StatusBadge } from '../../../shared/ui/status-badge/status-badge';
import { Pagination } from '../../../shared/ui/pagination/pagination';
import { DetailDrawer } from '../../../shared/ui/drawer/detail-drawer';
import { PostApiService, PostStats } from '../shared/post-api.service';
import { Post } from '../shared/post.model';
import { POST_STATUS_META } from '../../../core/models/status.model';
import { FanpageApiService } from '../../facebook/shared/facebook-api.service';
import { Fanpage } from '../../facebook/shared/facebook.model';
import { NotifyService } from '../../../core/notify/notify.service';
import { ApiError, Paginator } from '../../../core/http/api.model';

@Component({
  selector: 'app-schedule-page',
  standalone: true,
  imports: [PageHeader, StatCard, StatusBadge, Pagination, DetailDrawer, FormsModule, RouterLink],
  templateUrl: './schedule-page.html'
})
export class SchedulePage implements OnInit {
  private readonly postApi = inject(PostApiService);
  private readonly fanpageApi = inject(FanpageApiService);
  private readonly notify = inject(NotifyService);

  protected readonly fileTextIcon = LucideFileText;
  protected readonly checkIcon = LucideCircleCheck;
  protected readonly clockIcon = LucideClock;
  protected readonly alertIcon = LucideAlertTriangle;
  protected readonly expiredIcon = LucideCircleOff;
  protected readonly postStatusMeta = POST_STATUS_META;
  protected readonly statusOptions = Object.entries(POST_STATUS_META).map(([value, meta]) => ({
    value: Number(value),
    label: meta.label
  }));

  protected readonly loading = signal(true);
  protected readonly posts = signal<Post[]>([]);
  protected readonly stats = signal<PostStats | null>(null);
  protected readonly paginator = signal<Paginator>({ page: 1, pageSize: 8, totalItems: 0, totalPages: 0 });
  protected readonly fanpages = signal<Fanpage[]>([]);

  protected readonly keyword = signal('');
  protected readonly statusFilter = signal<number | ''>('');
  protected readonly fanpageFilter = signal<number | ''>('');

  protected readonly selectedPost = signal<Post | null>(null);
  protected readonly activeTab = signal<'info' | 'history'>('info');

  ngOnInit(): void {
    this.fanpageApi.list({ pageSize: 100 }).subscribe((res) => this.fanpages.set(res.result));
    this.loadStats();
    this.load(1);
  }

  protected applyFilters(): void {
    this.load(1);
  }

  protected openDetail(post: Post): void {
    this.selectedPost.set(post);
    this.activeTab.set('info');
  }

  protected closeDetail(): void {
    this.selectedPost.set(null);
  }

  protected cancelSchedule(post: Post): void {
    if (!confirm('Hủy lịch đăng bài viết này và chuyển về nháp?')) {
      return;
    }
    this.postApi.updateStatus(post.id, 1).subscribe({
      next: () => {
        this.notify.success('Đã hủy lịch đăng.');
        this.closeDetail();
        this.load(this.paginator().page);
        this.loadStats();
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể hủy lịch đăng.')
    });
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return '-';
    }
    return format(new Date(value.replace(' ', 'T')), 'dd/MM/yyyy HH:mm');
  }

  protected load(page: number): void {
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
          this.notify.error(err instanceof ApiError ? err.message : 'Không tải được lịch đăng.');
        }
      });
  }

  private loadStats(): void {
    this.postApi.stats(this.currentFilterPayload()).subscribe((stats) => this.stats.set(stats));
  }

  private currentFilterPayload() {
    return {
      keyword: this.keyword() || undefined,
      status: this.statusFilter() === '' ? undefined : Number(this.statusFilter()),
      fanpageId: this.fanpageFilter() === '' ? undefined : Number(this.fanpageFilter())
    };
  }
}
