import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { format } from 'date-fns';
import { LucideUsers, LucideShieldCheck, LucideClock, LucideTriangleAlert } from '@lucide/angular';
import { PageHeader } from '../../../shared/ui/page-header/page-header';
import { StatCard } from '../../../shared/ui/stat-card/stat-card';
import { StatusBadge } from '../../../shared/ui/status-badge/status-badge';
import { Pagination } from '../../../shared/ui/pagination/pagination';
import { DetailDrawer } from '../../../shared/ui/drawer/detail-drawer';
import { FacebookAccountApiService, FacebookAccountStats } from '../shared/facebook-api.service';
import { FacebookAccount } from '../shared/facebook.model';
import { FACEBOOK_ACCOUNT_STATUS_META, COOKIE_STATUS_META } from '../../../core/models/status.model';
import { NotifyService } from '../../../core/notify/notify.service';
import { ApiError, Paginator } from '../../../core/http/api.model';

@Component({
  selector: 'app-account-page',
  standalone: true,
  imports: [PageHeader, StatCard, StatusBadge, Pagination, DetailDrawer, FormsModule],
  templateUrl: './account-page.html'
})
export class AccountPage implements OnInit {
  private readonly accountApi = inject(FacebookAccountApiService);
  private readonly notify = inject(NotifyService);

  protected readonly usersIcon = LucideUsers;
  protected readonly shieldIcon = LucideShieldCheck;
  protected readonly clockIcon = LucideClock;
  protected readonly alertIcon = LucideTriangleAlert;
  protected readonly statusMeta = FACEBOOK_ACCOUNT_STATUS_META;
  protected readonly cookieStatusMeta = COOKIE_STATUS_META;
  protected readonly statusOptions = Object.entries(FACEBOOK_ACCOUNT_STATUS_META).map(([value, meta]) => ({
    value: Number(value),
    label: meta.label
  }));

  protected readonly loading = signal(true);
  protected readonly accounts = signal<FacebookAccount[]>([]);
  protected readonly stats = signal<FacebookAccountStats | null>(null);
  protected readonly paginator = signal<Paginator>({ page: 1, pageSize: 10, totalItems: 0, totalPages: 0 });
  protected readonly keyword = signal('');
  protected readonly statusFilter = signal<number | ''>('');
  protected readonly selected = signal<FacebookAccount | null>(null);
  protected readonly activeTab = signal<'info' | 'log'>('info');

  ngOnInit(): void {
    this.loadStats();
    this.load(1);
  }

  protected applyFilters(): void {
    this.load(1);
  }

  protected openDetail(account: FacebookAccount): void {
    this.accountApi.detail(account.id).subscribe((full) => {
      this.selected.set(full);
      this.activeTab.set('info');
    });
  }

  protected closeDetail(): void {
    this.selected.set(null);
  }

  protected reLogin(account: FacebookAccount): void {
    this.accountApi.reLogin(account.id).subscribe({
      next: () => {
        this.notify.success('Đã yêu cầu đăng nhập lại.');
        this.load(this.paginator().page);
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể đăng nhập lại.')
    });
  }

  protected deleteAccount(account: FacebookAccount): void {
    if (!confirm(`Xóa tài khoản "${account.displayName}"?`)) {
      return;
    }
    this.accountApi.delete(account.id).subscribe({
      next: () => {
        this.notify.success('Đã xóa tài khoản.');
        this.closeDetail();
        this.load(this.paginator().page);
        this.loadStats();
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Xóa tài khoản thất bại.')
    });
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return '-';
    }
    return format(new Date(value.replace(' ', 'T')), 'dd/MM/yyyy HH:mm');
  }

  private loadStats(): void {
    this.accountApi.stats().subscribe((stats) => this.stats.set(stats));
  }

  protected load(page: number): void {
    this.loading.set(true);
    this.accountApi
      .list({
        page,
        pageSize: this.paginator().pageSize,
        keyword: this.keyword() || undefined,
        status: this.statusFilter() === '' ? undefined : Number(this.statusFilter())
      })
      .subscribe({
        next: (res) => {
          this.accounts.set(res.result);
          this.paginator.set(res.paginator);
          this.loading.set(false);
        },
        error: (err: unknown) => {
          this.loading.set(false);
          this.notify.error(err instanceof ApiError ? err.message : 'Không tải được danh sách tài khoản.');
        }
      });
  }
}
