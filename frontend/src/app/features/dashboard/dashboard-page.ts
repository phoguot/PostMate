import { Component, OnDestroy, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import { format } from 'date-fns';
import {
  LucideAlertTriangle,
  LucideCircleCheck,
  LucideClock,
  LucideFileText,
  LucideRefreshCw
} from '@lucide/angular';
import { PageHeader } from '../../shared/ui/page-header/page-header';
import { StatCard } from '../../shared/ui/stat-card/stat-card';
import { StatusBadge } from '../../shared/ui/status-badge/status-badge';
import { BarChart } from '../../shared/ui/charts/bar-chart';
import { DonutChart } from '../../shared/ui/charts/donut-chart';
import { BarChartGroup } from '../../shared/ui/charts/bar-chart.model';
import { DonutSlice } from '../../shared/ui/charts/donut-chart.model';
import { DashboardApiService } from './dashboard-api.service';
import { DashboardDistribution, DashboardHealth, DashboardOverview } from './dashboard.model';
import { Post } from '../posting/shared/post.model';
import { POST_STATUS_META } from '../../core/models/status.model';
import { NotifyService } from '../../core/notify/notify.service';
import { ApiError } from '../../core/http/api.model';

function toDateInputValue(date: Date): string {
  return format(date, 'yyyy-MM-dd');
}

@Component({
  selector: 'app-dashboard-page',
  standalone: true,
  imports: [PageHeader, StatCard, StatusBadge, BarChart, DonutChart, RouterLink, FormsModule],
  templateUrl: './dashboard-page.html'
})
export class DashboardPage implements OnInit, OnDestroy {
  private readonly dashboardApi = inject(DashboardApiService);
  private readonly notify = inject(NotifyService);

  protected readonly fileTextIcon = LucideFileText;
  protected readonly checkIcon = LucideCircleCheck;
  protected readonly clockIcon = LucideClock;
  protected readonly alertIcon = LucideAlertTriangle;
  protected readonly refreshIcon = LucideRefreshCw;
  protected readonly postStatusMeta = POST_STATUS_META;

  protected readonly loading = signal(true);
  protected readonly autoRefresh = signal(false);
  protected readonly fromDate = signal(toDateInputValue(new Date(Date.now() - 30 * 86400_000)));
  protected readonly toDate = signal(toDateInputValue(new Date()));

  protected readonly overview = signal<DashboardOverview | null>(null);
  protected readonly performanceGroups = signal<BarChartGroup[]>([]);
  protected readonly distribution = signal<DashboardDistribution | null>(null);
  protected readonly donutSlices = signal<DonutSlice[]>([]);
  protected readonly health = signal<DashboardHealth | null>(null);
  protected readonly recentPosts = signal<Post[]>([]);

  private refreshTimer?: ReturnType<typeof setInterval>;

  ngOnInit(): void {
    this.load();
  }

  ngOnDestroy(): void {
    this.stopAutoRefresh();
  }

  protected applyRange(): void {
    this.load();
  }

  protected toggleAutoRefresh(): void {
    this.autoRefresh.update((v) => !v);
    if (this.autoRefresh()) {
      this.refreshTimer = setInterval(() => this.load(), 30_000);
    } else {
      this.stopAutoRefresh();
    }
  }

  private stopAutoRefresh(): void {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
      this.refreshTimer = undefined;
    }
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return '-';
    }
    return format(new Date(value.replace(' ', 'T')), 'dd/MM/yyyy HH:mm');
  }

  protected formatDelta(delta: number | null): string {
    if (delta === null) {
      return '';
    }
    const sign = delta > 0 ? '+' : '';
    return `${sign}${delta}%`;
  }

  private load(): void {
    this.loading.set(true);
    const payload = { fromDate: this.fromDate(), toDate: this.toDate() };

    forkJoin({
      overview: this.dashboardApi.overview(payload),
      performance: this.dashboardApi.performance(payload),
      distribution: this.dashboardApi.distribution(payload),
      health: this.dashboardApi.health(),
      recent: this.dashboardApi.recent(6)
    }).subscribe({
      next: ({ overview, performance, distribution, health, recent }) => {
        this.overview.set(overview);
        this.performanceGroups.set(
          performance.map((p) => ({
            label: p.date.slice(5),
            segments: [
              { value: p.published, colorVar: 'var(--color-status-success)' },
              { value: p.pending, colorVar: 'var(--color-status-warning)' },
              { value: p.failed, colorVar: 'var(--color-status-danger)' }
            ]
          }))
        );
        this.distribution.set(distribution);
        this.donutSlices.set(
          distribution.distribution
            .filter((d) => d.count > 0)
            .map((d) => ({ label: d.name, value: d.count, colorVar: this.colorForStatus(d.status) }))
        );
        this.health.set(health);
        this.recentPosts.set(recent);
        this.loading.set(false);
      },
      error: (err: unknown) => {
        this.loading.set(false);
        this.notify.error(err instanceof ApiError ? err.message : 'Không tải được dữ liệu Dashboard.');
      }
    });
  }

  private colorForStatus(status: number): string {
    const tone = this.postStatusMeta[status]?.tone ?? 'neutral';
    return `var(--color-status-${tone})`;
  }
}
