export interface Settings {
  language: string | null;
  timezone: string | null;
  dateFormat: string | null;
  themeMode: string | null;
  displayDensity: string | null;
  defaultFanpage: { id: number; name: string } | null;
  defaultContentType: number | null;
  defaultStatus: number | null;
  defaultPostTime: string | null;
  autoShortenLink: boolean;
  autoSaveDraft: boolean;
  showAiSuggestions: boolean;
  confirmBeforePost: boolean;
  confirmBeforeDelete: boolean;
  autoSaveChanges: boolean;
  notificationSound: boolean;
  showQuickHints: boolean;
  performanceTracking: boolean;
  preferredChannel: number | null;
  allowBrowserFallback: boolean;
  storageUsed: number;
  storageLimit: number;
  appVersion: string | null;
  lastBackupAt: string | null;
  updatedAt: number | null;
}

export interface SettingsUpdatePayload {
  language?: string;
  timezone?: string;
  dateFormat?: string;
  themeMode?: string;
  displayDensity?: string;
  defaultFanpageId?: number;
  defaultContentType?: number;
  defaultStatus?: number;
  defaultPostTime?: string;
}

export interface ActivityLogActor {
  id: number | null;
  name: string | null;
  avatar: string | null;
  role: string | null;
}

export interface ActivityLogItem {
  id: number;
  entityRef: string | null;
  type: string | null;
  message: string | null;
  level: number;
  actor: ActivityLogActor;
  objectName: string | null;
  objectType: string | null;
  ipAddress: string | null;
  device: string | null;
  createdAt: number;
}

export interface ActivityLogFilter {
  page?: number;
  pageSize?: number;
  keyword?: string;
  type?: string;
  objectType?: string;
  level?: number;
  dateFrom?: string;
  dateTo?: string;
}
