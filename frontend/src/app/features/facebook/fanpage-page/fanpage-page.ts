import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { format } from 'date-fns';
import { LucideFlag, LucideShieldCheck, LucideClock, LucideCircleX } from '@lucide/angular';
import { PageHeader } from '../../../shared/ui/page-header/page-header';
import { StatCard } from '../../../shared/ui/stat-card/stat-card';
import { StatusBadge } from '../../../shared/ui/status-badge/status-badge';
import { Pagination } from '../../../shared/ui/pagination/pagination';
import { DetailDrawer } from '../../../shared/ui/drawer/detail-drawer';
import { FanpageApiService, FanpageStats } from '../shared/facebook-api.service';
import { Fanpage } from '../shared/facebook.model';
import { FANPAGE_STATUS_META } from '../../../core/models/status.model';
import { NotifyService } from '../../../core/notify/notify.service';
import { ApiError, Paginator } from '../../../core/http/api.model';

@Component({
  selector: 'app-fanpage-page',
  standalone: true,
  imports: [PageHeader, StatCard, StatusBadge, Pagination, DetailDrawer, FormsModule],
  templateUrl: './fanpage-page.html'
})
export class FanpagePage implements OnInit {
  private readonly fanpageApi = inject(FanpageApiService);
  private readonly notify = inject(NotifyService);

  protected readonly flagIcon = LucideFlag;
  protected readonly shieldIcon = LucideShieldCheck;
  protected readonly clockIcon = LucideClock;
  protected readonly xIcon = LucideCircleX;
  protected readonly statusMeta = FANPAGE_STATUS_META;
  protected readonly statusOptions = Object.entries(FANPAGE_STATUS_META).map(([value, meta]) => ({
    value: Number(value),
    label: meta.label
  }));

  protected readonly loading = signal(true);
  protected readonly fanpages = signal<Fanpage[]>([]);
  protected readonly stats = signal<FanpageStats | null>(null);
  protected readonly paginator = signal<Paginator>({ page: 1, pageSize: 10, totalItems: 0, totalPages: 0 });
  protected readonly keyword = signal('');
  protected readonly statusFilter = signal<number | ''>('');
  protected readonly selected = signal<Fanpage | null>(null);
  protected readonly activeTab = signal<'info' | 'log'>('info');

  ngOnInit(): void {
    this.loadStats();
    this.load(1);
  }

  protected applyFilters(): void {
    this.load(1);
  }

  protected openDetail(fanpage: Fanpage): void {
    this.fanpageApi.detail(fanpage.id).subscribe((full) => {
      this.selected.set(full);
      this.activeTab.set('info');
    });
  }

  protected closeDetail(): void {
    this.selected.set(null);
  }

  protected reLogin(fanpage: Fanpage): void {
    this.fanpageApi.reLogin(fanpage.id).subscribe({
      next: () => {
        this.notify.success('Đã yêu cầu đăng nhập lại.');
        this.load(this.paginator().page);
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể đăng nhập lại.')
    });
  }

  protected unlink(fanpage: Fanpage): void {
    if (!confirm(`Gỡ liên kết fanpage "${fanpage.name}"?`)) {
      return;
    }
    this.fanpageApi.unlink(fanpage.id).subscribe({
      next: () => {
        this.notify.success('Đã gỡ liên kết fanpage.');
        this.closeDetail();
        this.load(this.paginator().page);
        this.loadStats();
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Gỡ liên kết thất bại.')
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
    this.fanpageApi
      .list({
        page,
        pageSize: this.paginator().pageSize,
        keyword: this.keyword() || undefined,
        status: this.statusFilter() === '' ? undefined : Number(this.statusFilter())
      })
      .subscribe({
        next: (res) => {
          this.fanpages.set(res.result);
          this.paginator.set(res.paginator);
          this.loading.set(false);
        },
        error: (err: unknown) => {
          this.loading.set(false);
          this.notify.error(err instanceof ApiError ? err.message : 'Không tải được danh sách fanpage.');
        }
      });
  }

  private loadStats(): void {
    this.fanpageApi.stats().subscribe((stats) => this.stats.set(stats));
  }
}
