import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { format } from 'date-fns';
import { LucideCookie, LucideShieldCheck, LucideClock, LucideTriangleAlert } from '@lucide/angular';
import { PageHeader } from '../../../shared/ui/page-header/page-header';
import { StatCard } from '../../../shared/ui/stat-card/stat-card';
import { StatusBadge } from '../../../shared/ui/status-badge/status-badge';
import { Pagination } from '../../../shared/ui/pagination/pagination';
import { DetailDrawer } from '../../../shared/ui/drawer/detail-drawer';
import { CookieApiService, CookieStats } from '../shared/facebook-api.service';
import { Cookie } from '../shared/facebook.model';
import { COOKIE_STATUS_META } from '../../../core/models/status.model';
import { NotifyService } from '../../../core/notify/notify.service';
import { ApiError, Paginator } from '../../../core/http/api.model';

@Component({
  selector: 'app-cookie-page',
  standalone: true,
  imports: [PageHeader, StatCard, StatusBadge, Pagination, DetailDrawer, FormsModule],
  templateUrl: './cookie-page.html'
})
export class CookiePage implements OnInit {
  private readonly cookieApi = inject(CookieApiService);
  private readonly notify = inject(NotifyService);

  protected readonly cookieIcon = LucideCookie;
  protected readonly shieldIcon = LucideShieldCheck;
  protected readonly clockIcon = LucideClock;
  protected readonly alertIcon = LucideTriangleAlert;
  protected readonly statusMeta = COOKIE_STATUS_META;
  protected readonly statusOptions = Object.entries(COOKIE_STATUS_META).map(([value, meta]) => ({
    value: Number(value),
    label: meta.label
  }));

  protected readonly loading = signal(true);
  protected readonly cookies = signal<Cookie[]>([]);
  protected readonly stats = signal<CookieStats | null>(null);
  protected readonly paginator = signal<Paginator>({ page: 1, pageSize: 10, totalItems: 0, totalPages: 0 });
  protected readonly keyword = signal('');
  protected readonly statusFilter = signal<number | ''>('');
  protected readonly selected = signal<Cookie | null>(null);
  protected readonly activeTab = signal<'info' | 'log'>('info');

  ngOnInit(): void {
    this.loadStats();
    this.load(1);
  }

  protected applyFilters(): void {
    this.load(1);
  }

  protected openDetail(cookie: Cookie): void {
    this.cookieApi.detail(cookie.id).subscribe((full) => {
      this.selected.set(full);
      this.activeTab.set('info');
    });
  }

  protected closeDetail(): void {
    this.selected.set(null);
  }

  protected login(cookie: Cookie): void {
    this.cookieApi.login(cookie.id).subscribe({
      next: () => {
        this.notify.success('Đã yêu cầu đăng nhập.');
        this.load(this.paginator().page);
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể đăng nhập.')
    });
  }

  protected refresh(cookie: Cookie): void {
    this.cookieApi.refresh(cookie.id).subscribe({
      next: () => {
        this.notify.success('Đã làm mới cookie.');
        this.load(this.paginator().page);
        this.loadStats();
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể làm mới cookie.')
    });
  }

  protected refreshAll(): void {
    this.cookieApi.refreshAll().subscribe({
      next: () => {
        this.notify.success('Đã làm mới toàn bộ cookie sắp hết hạn.');
        this.load(this.paginator().page);
        this.loadStats();
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể làm mới cookie.')
    });
  }

  protected deleteCookie(cookie: Cookie): void {
    if (!confirm(`Xóa cookie "${cookie.code}"?`)) {
      return;
    }
    this.cookieApi.delete(cookie.id).subscribe({
      next: () => {
        this.notify.success('Đã xóa cookie.');
        this.closeDetail();
        this.load(this.paginator().page);
        this.loadStats();
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Xóa cookie thất bại.')
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
    this.cookieApi
      .list({
        page,
        pageSize: this.paginator().pageSize,
        keyword: this.keyword() || undefined,
        status: this.statusFilter() === '' ? undefined : Number(this.statusFilter())
      })
      .subscribe({
        next: (res) => {
          this.cookies.set(res.result);
          this.paginator.set(res.paginator);
          this.loading.set(false);
        },
        error: (err: unknown) => {
          this.loading.set(false);
          this.notify.error(err instanceof ApiError ? err.message : 'Không tải được danh sách cookie.');
        }
      });
  }

  private loadStats(): void {
    this.cookieApi.stats().subscribe((stats) => this.stats.set(stats));
  }
}
