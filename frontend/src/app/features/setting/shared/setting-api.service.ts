import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from '../../../core/http/api.service';
import { PaginatedResult } from '../../../core/http/api.model';
import { ActivityLogFilter, ActivityLogItem, Settings, SettingsUpdatePayload } from './setting.model';

export interface SystemInfo {
  appVersion: string | null;
  lastBackupAt: string | null;
  storageUsed: number;
  storageLimit: number;
}

export interface ExportResult {
  fileName: string;
  generatedAt: string;
  downloadUrl: string | null;
}

@Injectable({ providedIn: 'root' })
export class SettingApiService {
  private readonly api = inject(ApiService);

  getSettings(): Observable<Settings> {
    return this.api.post<Settings>('/setting/index', {});
  }

  updateSettings(payload: SettingsUpdatePayload): Observable<Settings> {
    return this.api.post<Settings>('/setting/update', payload as Record<string, unknown>);
  }

  toggle(key: string, value: boolean): Observable<Settings> {
    return this.api.post<Settings>('/setting/toggle', { key, value });
  }

  systemInfo(): Observable<SystemInfo> {
    return this.api.post<SystemInfo>('/setting/systeminfo', {});
  }

  backupNow(): Observable<{ lastBackupAt: string }> {
    return this.api.post<{ lastBackupAt: string }>('/setting/backup', {});
  }

  exportData(): Observable<ExportResult> {
    return this.api.post<ExportResult>('/setting/export', {});
  }

  resetSettings(): Observable<Settings> {
    return this.api.post<Settings>('/setting/reset', {});
  }

  listActivityLog(filter: ActivityLogFilter): Observable<PaginatedResult<ActivityLogItem>> {
    return this.api.post<PaginatedResult<ActivityLogItem>>('/setting/activitylog', filter as Record<string, unknown>);
  }
}
