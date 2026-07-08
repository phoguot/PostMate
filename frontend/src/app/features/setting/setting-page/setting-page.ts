import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { LucideIconInput } from '@lucide/angular';
import {
  LucideBell,
  LucideBot,
  LucideCalendarDays,
  LucideCreditCard,
  LucideHelpCircle,
  LucideHistory,
  LucideLayoutGrid,
  LucideLink2,
  LucideSettings,
  LucideShieldCheck,
  LucideUser,
  LucideUsers
} from '@lucide/angular';
import { PageHeader } from '../../../shared/ui/page-header/page-header';
import { AppIcon } from '../../../shared/ui/icon/app-icon';
import { OverviewPanel } from './panels/overview-panel/overview-panel';
import { AccountPanel } from './panels/account-panel/account-panel';
import { GeneralPanel } from './panels/general-panel/general-panel';
import { FacebookSettingPanel } from './panels/facebook-panel/facebook-panel';
import { ActivityLogPanel } from './panels/activity-log-panel/activity-log-panel';

interface SettingNavItem {
  id: string;
  label: string;
  subtitle: string;
  icon: LucideIconInput | null;
  implemented: boolean;
}

@Component({
  selector: 'app-setting-page',
  standalone: true,
  imports: [PageHeader, AppIcon, OverviewPanel, AccountPanel, GeneralPanel, FacebookSettingPanel, ActivityLogPanel],
  templateUrl: './setting-page.html'
})
export class SettingPage {
  private readonly route = inject(ActivatedRoute);

  protected readonly helpIcon = LucideHelpCircle;
  protected readonly historyIcon = LucideHistory;

  protected readonly navItems: SettingNavItem[] = [
    { id: 'overview', label: 'Tổng quan', subtitle: 'Thiết lập chung của hệ thống', icon: LucideLayoutGrid, implemented: true },
    { id: 'account', label: 'Tài khoản', subtitle: 'Thông tin tài khoản và bảo mật', icon: LucideUser, implemented: true },
    { id: 'notification', label: 'Thông báo', subtitle: 'Quản lý thông báo hệ thống', icon: LucideBell, implemented: false },
    { id: 'security', label: 'Bảo mật', subtitle: 'Đổi mật khẩu và xác thực 2 lớp', icon: LucideShieldCheck, implemented: false },
    { id: 'billing', label: 'Thanh toán', subtitle: 'Quản lý gói dịch vụ và hóa đơn', icon: LucideCreditCard, implemented: false },
    { id: 'member', label: 'Thành viên', subtitle: 'Quản lý thành viên trong nhóm', icon: LucideUsers, implemented: false },
    { id: 'facebook', label: 'Facebook', subtitle: 'Kết nối và quản lý tài khoản Facebook', icon: null, implemented: true },
    { id: 'schedule', label: 'Lịch đăng', subtitle: 'Cấu hình lịch đăng bài viết', icon: LucideCalendarDays, implemented: false },
    { id: 'activity', label: 'Nhật ký hệ thống', subtitle: 'Theo dõi hoạt động của hệ thống', icon: LucideHistory, implemented: true },
    { id: 'connect', label: 'Kết nối', subtitle: 'Quản lý tích hợp bên thứ ba', icon: LucideLink2, implemented: false },
    { id: 'ai', label: 'AI & nội dung', subtitle: 'Cấu hình AI và nội dung', icon: LucideBot, implemented: false },
    { id: 'general', label: 'Cài đặt chung', subtitle: 'Tùy chọn chung của hệ thống', icon: LucideSettings, implemented: true }
  ];

  protected readonly activeNav = signal(this.route.snapshot.queryParamMap.get('tab') ?? 'overview');

  protected selectNav(id: string): void {
    this.activeNav.set(id);
  }

  protected activeItem(): SettingNavItem {
    return this.navItems.find((item) => item.id === this.activeNav()) ?? this.navItems[0];
  }
}
