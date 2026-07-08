import { Component, OnInit, inject, signal } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { format } from 'date-fns';
import { LucideCheckCircle2, LucidePauseCircle, LucideRefreshCw, LucideShieldCheck, LucideXCircle } from '@lucide/angular';
import { StatCard } from '../../../../../shared/ui/stat-card/stat-card';
import { StatusBadge } from '../../../../../shared/ui/status-badge/status-badge';
import { Pagination } from '../../../../../shared/ui/pagination/pagination';
import { AppIcon } from '../../../../../shared/ui/icon/app-icon';
import { FacebookAccountApiService, FacebookAccountStats } from '../../../../facebook/shared/facebook-api.service';
import { FacebookAccount } from '../../../../facebook/shared/facebook.model';
import { ConnectFacebookPopup } from '../../../../facebook/shared/connect-facebook-popup/connect-facebook-popup';
import { FACEBOOK_ACCOUNT_STATUS_META } from '../../../../../core/models/status.model';
import { NotifyService } from '../../../../../core/notify/notify.service';
import { ApiError, Paginator } from '../../../../../core/http/api.model';

@Component({
  selector: 'app-facebook-setting-panel',
  standalone: true,
  imports: [FormsModule, StatCard, StatusBadge, Pagination, AppIcon, ConnectFacebookPopup],
  templateUrl: './facebook-panel.html'
})
export class FacebookSettingPanel implements OnInit {
  private readonly accountApi = inject(FacebookAccountApiService);
  private readonly notify = inject(NotifyService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  protected readonly checkIcon = LucideCheckCircle2;
  protected readonly pauseIcon = LucidePauseCircle;
  protected readonly xIcon = LucideXCircle;
  protected readonly refreshIcon = LucideRefreshCw;
  protected readonly shieldIcon = LucideShieldCheck;

  protected readonly statusMeta = FACEBOOK_ACCOUNT_STATUS_META;
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
  protected readonly showConnectPopup = signal(false);

  ngOnInit(): void {
    this.loadStats();
    this.load(1);
    this.handleOAuthReturn();
  }

  /** Xử lý kết quả sau khi Facebook redirect về (?fbConnect=success|error&message=...). */
  private handleOAuthReturn(): void {
    const params = this.route.snapshot.queryParamMap;
    const fbConnect = params.get('fbConnect');
    if (!fbConnect) {
      return;
    }
    if (fbConnect === 'success') {
      this.notify.success('Đã kết nối tài khoản Facebook.');
      this.loadStats();
      this.load(1);
    } else {
      this.notify.error(params.get('message') || 'Kết nối tài khoản Facebook thất bại.');
    }
    this.router.navigate([], { queryParams: { tab: 'facebook' }, replaceUrl: true });
  }

  protected applyFilters(): void {
    this.load(1);
  }

  protected refresh(): void {
    this.loadStats();
    this.load(this.paginator().page);
  }

  protected openConnectPopup(): void {
    this.showConnectPopup.set(true);
  }

  protected closeConnectPopup(): void {
    this.showConnectPopup.set(false);
  }

  /** Điều hướng sang trang cấp quyền OAuth thật của Facebook (facebook.com/dialog/oauth). */
  protected confirmConnect(): void {
    this.accountApi.connect().subscribe({
      next: (res) => {
        window.location.href = res.authorizeUrl;
      },
      error: (err: unknown) => {
        this.showConnectPopup.set(false);
        this.notify.error(err instanceof ApiError ? err.message : 'Không thể kết nối tài khoản Facebook.');
      }
    });
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

  protected daysLeft(expiresAt: string | null): number | null {
    if (!expiresAt) {
      return null;
    }
    const diffMs = new Date(expiresAt.replace(' ', 'T')).getTime() - Date.now();
    return Math.ceil(diffMs / (1000 * 60 * 60 * 24));
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
