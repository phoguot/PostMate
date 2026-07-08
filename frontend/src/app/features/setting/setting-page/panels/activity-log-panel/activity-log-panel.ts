import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { format } from 'date-fns';
import { LucideFilter } from '@lucide/angular';
import { Pagination } from '../../../../../shared/ui/pagination/pagination';
import { AppIcon } from '../../../../../shared/ui/icon/app-icon';
import { SettingApiService } from '../../../shared/setting-api.service';
import { ActivityLogItem } from '../../../shared/setting.model';
import { NotifyService } from '../../../../../core/notify/notify.service';
import { ApiError, Paginator } from '../../../../../core/http/api.model';

@Component({
  selector: 'app-activity-log-panel',
  standalone: true,
  imports: [FormsModule, Pagination, AppIcon],
  templateUrl: './activity-log-panel.html'
})
export class ActivityLogPanel implements OnInit {
  private readonly settingApi = inject(SettingApiService);
  private readonly notify = inject(NotifyService);

  protected readonly filterIcon = LucideFilter;

  protected readonly typeOptions = [
    'Đăng bài viết',
    'Cập nhật bài viết',
    'Lên lịch bài viết',
    'Xóa bài viết',
    'Thêm thành viên',
    'Thay đổi cài đặt'
  ];
  protected readonly objectTypeOptions = ['Trang Facebook', 'Nhóm', 'Hệ thống'];

  protected readonly loading = signal(true);
  protected readonly logs = signal<ActivityLogItem[]>([]);
  protected readonly paginator = signal<Paginator>({ page: 1, pageSize: 10, totalItems: 0, totalPages: 0 });

  protected readonly keyword = signal('');
  protected readonly type = signal('');
  protected readonly objectType = signal('');
  protected readonly dateFrom = signal('');
  protected readonly dateTo = signal('');

  ngOnInit(): void {
    this.load(1);
  }

  protected applyFilters(): void {
    this.load(1);
  }

  protected changePageSize(pageSize: number): void {
    this.paginator.update((p) => ({ ...p, pageSize }));
    this.load(1);
  }

  protected formatDateTime(epochSeconds: number): string {
    return format(new Date(epochSeconds * 1000), 'dd/MM/yyyy HH:mm:ss');
  }

  protected load(page: number): void {
    this.loading.set(true);
    this.settingApi
      .listActivityLog({
        page,
        pageSize: this.paginator().pageSize,
        keyword: this.keyword() || undefined,
        type: this.type() || undefined,
        objectType: this.objectType() || undefined,
        dateFrom: this.dateFrom() || undefined,
        dateTo: this.dateTo() || undefined
      })
      .subscribe({
        next: (res) => {
          this.logs.set(res.result);
          this.paginator.set(res.paginator);
          this.loading.set(false);
        },
        error: (err: unknown) => {
          this.loading.set(false);
          this.notify.error(err instanceof ApiError ? err.message : 'Không tải được nhật ký hệ thống.');
        }
      });
  }
}
