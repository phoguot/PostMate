import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Observable } from 'rxjs';
import { format } from 'date-fns';
import { LucideAppWindow, LucideCircleCheck, LucideCircleX, LucideWifiOff } from '@lucide/angular';
import { PageHeader } from '../../../shared/ui/page-header/page-header';
import { StatCard } from '../../../shared/ui/stat-card/stat-card';
import { StatusBadge } from '../../../shared/ui/status-badge/status-badge';
import { Pagination } from '../../../shared/ui/pagination/pagination';
import { DetailDrawer } from '../../../shared/ui/drawer/detail-drawer';
import { BrowserProfileApiService, BrowserProfileStats } from '../shared/browser-profile-api.service';
import { BrowserProfile } from '../shared/browser-profile.model';
import { BROWSER_PROFILE_STATUS_META, BrowserProfileStatus } from '../../../core/models/status.model';
import { NotifyService } from '../../../core/notify/notify.service';
import { ApiError, Paginator } from '../../../core/http/api.model';

@Component({
  selector: 'app-browser-profile-page',
  standalone: true,
  imports: [PageHeader, StatCard, StatusBadge, Pagination, DetailDrawer, FormsModule],
  templateUrl: './browser-profile-page.html'
})
export class BrowserProfilePage implements OnInit {
  private readonly browserApi = inject(BrowserProfileApiService);
  private readonly notify = inject(NotifyService);

  protected readonly appWindowIcon = LucideAppWindow;
  protected readonly checkIcon = LucideCircleCheck;
  protected readonly stopIcon = LucideCircleX;
  protected readonly offlineIcon = LucideWifiOff;
  protected readonly statusMeta = BROWSER_PROFILE_STATUS_META;
  protected readonly BrowserProfileStatus = BrowserProfileStatus;
  protected readonly statusOptions = Object.entries(BROWSER_PROFILE_STATUS_META).map(([value, meta]) => ({
    value: Number(value),
    label: meta.label
  }));

  protected readonly loading = signal(true);
  protected readonly profiles = signal<BrowserProfile[]>([]);
  protected readonly stats = signal<BrowserProfileStats | null>(null);
  protected readonly paginator = signal<Paginator>({ page: 1, pageSize: 10, totalItems: 0, totalPages: 0 });
  protected readonly keyword = signal('');
  protected readonly statusFilter = signal<number | ''>('');
  protected readonly selected = signal<BrowserProfile | null>(null);
  protected readonly activeTab = signal<'info' | 'log'>('info');

  ngOnInit(): void {
    this.loadStats();
    this.load(1);
  }

  protected applyFilters(): void {
    this.load(1);
  }

  protected openDetail(profile: BrowserProfile): void {
    this.browserApi.detail(profile.id).subscribe((full) => {
      this.selected.set(full);
      this.activeTab.set('info');
    });
  }

  protected closeDetail(): void {
    this.selected.set(null);
  }

  protected start(profile: BrowserProfile): void {
    this.runAction(this.browserApi.start(profile.id), 'Đã khởi động trình duyệt.');
  }

  protected stop(profile: BrowserProfile): void {
    this.runAction(this.browserApi.stop(profile.id), 'Đã dừng trình duyệt.');
  }

  protected restart(profile: BrowserProfile): void {
    this.runAction(this.browserApi.restart(profile.id), 'Đã khởi động lại trình duyệt.');
  }

  protected open(profile: BrowserProfile): void {
    this.browserApi.open(profile.id).subscribe({
      next: () => this.notify.success('Đã gửi yêu cầu mở trình duyệt.'),
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể mở trình duyệt.')
    });
  }

  protected deleteProfile(profile: BrowserProfile): void {
    if (!confirm(`Xóa trình duyệt "${profile.profileName}"?`)) {
      return;
    }
    this.browserApi.delete(profile.id).subscribe({
      next: () => {
        this.notify.success('Đã xóa trình duyệt.');
        this.closeDetail();
        this.load(this.paginator().page);
        this.loadStats();
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Xóa trình duyệt thất bại.')
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
    this.browserApi
      .list({
        page,
        pageSize: this.paginator().pageSize,
        keyword: this.keyword() || undefined,
        status: this.statusFilter() === '' ? undefined : Number(this.statusFilter())
      })
      .subscribe({
        next: (res) => {
          this.profiles.set(res.result);
          this.paginator.set(res.paginator);
          this.loading.set(false);
        },
        error: (err: unknown) => {
          this.loading.set(false);
          this.notify.error(err instanceof ApiError ? err.message : 'Không tải được danh sách trình duyệt.');
        }
      });
  }

  private loadStats(): void {
    this.browserApi.stats().subscribe((stats) => this.stats.set(stats));
  }

  private runAction(obs: Observable<unknown>, successMsg: string): void {
    obs.subscribe({
      next: () => {
        this.notify.success(successMsg);
        this.load(this.paginator().page);
        this.loadStats();
        if (this.selected()) {
          this.openDetail(this.selected()!);
        }
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Thao tác thất bại.')
    });
  }
}
