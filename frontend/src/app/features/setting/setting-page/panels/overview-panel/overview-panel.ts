import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { format } from 'date-fns';
import { SettingApiService, SystemInfo } from '../../../shared/setting-api.service';
import { Settings } from '../../../shared/setting.model';
import { AuthService } from '../../../../../core/auth/auth.service';
import { NotifyService } from '../../../../../core/notify/notify.service';
import { ApiError } from '../../../../../core/http/api.model';
import { FanpageApiService } from '../../../../facebook/shared/facebook-api.service';
import { Fanpage } from '../../../../facebook/shared/facebook.model';
import { POST_CONTENT_TYPE_META, POST_STATUS_META } from '../../../../../core/models/status.model';

@Component({
  selector: 'app-overview-panel',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './overview-panel.html'
})
export class OverviewPanel implements OnInit {
  private readonly settingApi = inject(SettingApiService);
  private readonly fanpageApi = inject(FanpageApiService);
  private readonly notify = inject(NotifyService);
  protected readonly auth = inject(AuthService);

  protected readonly settings = signal<Settings | null>(null);
  protected readonly systemInfo = signal<SystemInfo | null>(null);
  protected readonly fanpages = signal<Fanpage[]>([]);

  protected readonly contentTypeOptions = Object.entries(POST_CONTENT_TYPE_META).map(([value, meta]) => ({
    value: Number(value),
    label: meta.label
  }));
  protected readonly statusOptions = Object.entries(POST_STATUS_META).map(([value, meta]) => ({
    value: Number(value),
    label: meta.label
  }));

  protected readonly language = signal('vi');
  protected readonly timezone = signal('Asia/Ho_Chi_Minh');
  protected readonly defaultFanpageId = signal<number | ''>('');
  protected readonly defaultContentType = signal<number | ''>('');
  protected readonly defaultStatus = signal<number | ''>('');
  protected readonly defaultPostTime = signal('09:00');

  ngOnInit(): void {
    this.settingApi.getSettings().subscribe((s) => this.applySettings(s));
    this.settingApi.systemInfo().subscribe((info) => this.systemInfo.set(info));
    this.fanpageApi.list({ pageSize: 100 }).subscribe((res) => this.fanpages.set(res.result));
  }

  protected saveLocale(): void {
    this.settingApi.updateSettings({ language: this.language(), timezone: this.timezone() }).subscribe({
      next: (s) => {
        this.applySettings(s);
        this.notify.success('Đã lưu ngôn ngữ & múi giờ.');
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể lưu thay đổi.')
    });
  }

  protected saveDefaults(): void {
    this.settingApi
      .updateSettings({
        defaultFanpageId: this.defaultFanpageId() === '' ? undefined : Number(this.defaultFanpageId()),
        defaultContentType: this.defaultContentType() === '' ? undefined : Number(this.defaultContentType()),
        defaultStatus: this.defaultStatus() === '' ? undefined : Number(this.defaultStatus()),
        defaultPostTime: `${this.defaultPostTime()}:00`
      })
      .subscribe({
        next: (s) => {
          this.applySettings(s);
          this.notify.success('Đã lưu cài đặt mặc định.');
        },
        error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể lưu thay đổi.')
      });
  }

  protected toggleOption(key: keyof Settings, currentValue: boolean): void {
    this.settingApi.toggle(key, !currentValue).subscribe({
      next: (s) => this.applySettings(s),
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể cập nhật.')
    });
  }

  protected backupNow(): void {
    this.settingApi.backupNow().subscribe({
      next: (res) => {
        this.notify.success('Đã sao lưu dữ liệu.');
        this.systemInfo.update((info) => (info ? { ...info, lastBackupAt: res.lastBackupAt } : info));
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Sao lưu thất bại.')
    });
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return '-';
    }
    return format(new Date(value.replace(' ', 'T')), 'dd/MM/yyyy HH:mm');
  }

  protected storagePercent(): number {
    const info = this.systemInfo();
    if (!info || !info.storageLimit) {
      return 0;
    }
    return Math.round((info.storageUsed / info.storageLimit) * 100);
  }

  private applySettings(s: Settings): void {
    this.settings.set(s);
    this.language.set(s.language ?? 'vi');
    this.timezone.set(s.timezone ?? 'Asia/Ho_Chi_Minh');
    this.defaultFanpageId.set(s.defaultFanpage?.id ?? '');
    this.defaultContentType.set(s.defaultContentType ?? '');
    this.defaultStatus.set(s.defaultStatus ?? '');
    this.defaultPostTime.set((s.defaultPostTime ?? '09:00:00').slice(0, 5));
  }
}
