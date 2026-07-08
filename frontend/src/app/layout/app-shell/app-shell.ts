import { Component, inject, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import {
  LucideAppWindow,
  LucideBot,
  LucideCalendarDays,
  LucideChevronDown,
  LucideCookie,
  LucideFileText,
  LucideFlag,
  LucideHistory,
  LucideLayoutDashboard,
  LucideLogOut,
  LucideSettings,
  LucideSquarePen,
  LucideUser
} from '@lucide/angular';
import { AppIcon } from '../../shared/ui/icon/app-icon';
import { AuthService } from '../../core/auth/auth.service';
import { NavItem } from './nav-item.model';

@Component({
  selector: 'app-shell',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, AppIcon],
  templateUrl: './app-shell.html'
})
export class AppShell {
  protected readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly logoIcon = LucideLayoutDashboard;
  protected readonly chevronDown = LucideChevronDown;
  protected readonly logOutIcon = LucideLogOut;

  protected readonly menuOpen = signal(false);

  protected readonly mainNav: NavItem[] = [
    { label: 'Dashboard', route: '/dashboard', icon: LucideLayoutDashboard },
    { label: 'Tạo nội dung', route: '/create-post', icon: LucideSquarePen },
    { label: 'Lịch đăng', route: '/schedule', icon: LucideCalendarDays },
    { label: 'Bài viết', route: '/posts', icon: LucideFileText }
  ];

  protected readonly facebookNav: NavItem[] = [
    { label: 'Tài khoản', route: '/accounts', icon: LucideUser },
    { label: 'Fanpage', route: '/fanpages', icon: LucideFlag },
    { label: 'Trình duyệt', route: '/browsers', icon: LucideAppWindow },
    { label: 'Cookie', route: '/cookies', icon: LucideCookie },
    { label: 'Nhật ký', route: '/activity-log', icon: LucideHistory }
  ];

  protected readonly bottomNav: NavItem[] = [
    { label: 'AI Agent', route: '/ai-agent', icon: LucideBot },
    { label: 'Cài đặt', route: '/settings', icon: LucideSettings }
  ];

  protected toggleMenu(): void {
    this.menuOpen.update((v) => !v);
  }

  protected logout(): void {
    this.menuOpen.set(false);
    this.auth.logout().subscribe(() => this.router.navigate(['/login']));
  }
}
