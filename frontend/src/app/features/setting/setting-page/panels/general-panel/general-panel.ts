import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { LucideDownload, LucideMonitor, LucideMoon, LucideRotateCcw, LucideSun } from '@lucide/angular';
import { AppIcon } from '../../../../../shared/ui/icon/app-icon';
import { SettingApiService } from '../../../shared/setting-api.service';
import { Settings } from '../../../shared/setting.model';
import { NotifyService } from '../../../../../core/notify/notify.service';
import { ApiError } from '../../../../../core/http/api.model';

@Component({
  selector: 'app-general-panel',
  standalone: true,
  imports: [FormsModule, AppIcon],
  templateUrl: './general-panel.html'
})
export class GeneralPanel implements OnInit {
  private readonly settingApi = inject(SettingApiService);
  private readonly notify = inject(NotifyService);

  protected readonly sunIcon = LucideSun;
  protected readonly moonIcon = LucideMoon;
  protected readonly monitorIcon = LucideMonitor;
  protected readonly downloadIcon = LucideDownload;
  protected readonly resetIcon = LucideRotateCcw;

  protected readonly settings = signal<Settings | null>(null);
  protected readonly themeMode = signal('light');
  protected readonly displayDensity = signal('standard');
  protected readonly language = signal('vi');
  protected readonly exporting = signal(false);
  protected readonly resetting = signal(false);

  protected readonly themeOptions = [
    { value: 'light', label: 'Sáng', icon: this.sunIcon },
    { value: 'dark', label: 'Tối', icon: this.moonIcon },
    { value: 'system', label: 'Theo hệ thống', icon: this.monitorIcon }
  ];

  protected readonly densityOptions = [
    { value: 'compact', label: 'Gọn' },
    { value: 'standard', label: 'Tiêu chuẩn' },
    { value: 'comfortable', label: 'Thoải mái' }
  ];

  ngOnInit(): void {
    this.settingApi.getSettings().subscribe((s) => this.applySettings(s));
  }

  protected selectTheme(value: string): void {
    this.themeMode.set(value);
    this.saveAppearance();
  }

  protected saveAppearance(): void {
    this.settingApi
      .updateSettings({ themeMode: this.themeMode(), displayDensity: this.displayDensity(), language: this.language() })
      .subscribe({
        next: (s) => {
          this.applySettings(s);
          this.notify.success('Đã lưu giao diện.');
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

  protected exportData(): void {
    if (this.exporting()) {
      return;
    }
    this.exporting.set(true);
    this.settingApi.exportData().subscribe({
      next: (res) => {
        this.exporting.set(false);
        this.notify.success(`Đã tạo file xuất dữ liệu: ${res.fileName}`);
      },
      error: (err: unknown) => {
        this.exporting.set(false);
        this.notify.error(err instanceof ApiError ? err.message : 'Xuất dữ liệu thất bại.');
      }
    });
  }

  protected resetSettings(): void {
    if (this.resetting() || !confirm('Đặt lại tất cả cài đặt về mặc định?')) {
      return;
    }
    this.resetting.set(true);
    this.settingApi.resetSettings().subscribe({
      next: (s) => {
        this.resetting.set(false);
        this.applySettings(s);
        this.notify.success('Đã đặt lại cài đặt về mặc định.');
      },
      error: (err: unknown) => {
        this.resetting.set(false);
        this.notify.error(err instanceof ApiError ? err.message : 'Đặt lại cài đặt thất bại.');
      }
    });
  }

  private applySettings(s: Settings): void {
    this.settings.set(s);
    this.themeMode.set(s.themeMode ?? 'light');
    this.displayDensity.set(s.displayDensity ?? 'standard');
    this.language.set(s.language ?? 'vi');
  }
}
