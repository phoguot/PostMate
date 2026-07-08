import { Component, output, signal } from '@angular/core';
import { LucideArrowLeft, LucideBarChart2, LucideChevronDown, LucideFlag, LucideIconInput, LucideLock, LucideMessageCircle, LucideShieldCheck, LucideUsers, LucideX } from '@lucide/angular';
import { AppIcon } from '../../../../shared/ui/icon/app-icon';

interface PermissionItem {
  key: string;
  icon: LucideIconInput;
  title: string;
  description: string;
  scopes: string[];
}

@Component({
  selector: 'app-connect-facebook-popup',
  standalone: true,
  imports: [AppIcon],
  templateUrl: './connect-facebook-popup.html'
})
export class ConnectFacebookPopup {
  protected readonly backIcon = LucideArrowLeft;
  protected readonly closeIcon = LucideX;
  protected readonly shieldIcon = LucideShieldCheck;
  protected readonly lockIcon = LucideLock;
  protected readonly chevronIcon = LucideChevronDown;

  protected readonly permissions: PermissionItem[] = [
    {
      key: 'manage',
      icon: LucideFlag,
      title: 'Quản lý trang và nội dung',
      description: 'Đọc và đăng bài, quản lý bình luận, tin nhắn cho các trang bạn quản lý.',
      scopes: ['pages_manage_posts', 'pages_read_engagement']
    },
    {
      key: 'insights',
      icon: LucideBarChart2,
      title: 'Truy cập thông tin hiệu suất',
      description: 'Đọc số liệu thống kê về bài viết, lượt tiếp cận, tương tác.',
      scopes: ['read_insights']
    },
    {
      key: 'audience',
      icon: LucideUsers,
      title: 'Quản lý danh sách fan & đối tượng',
      description: 'Đọc thông tin người theo dõi và đối tượng của trang.',
      scopes: ['pages_read_user_content']
    },
    {
      key: 'inbox',
      icon: LucideMessageCircle,
      title: 'Quản lý tin nhắn',
      description: 'Đọc và phản hồi tin nhắn từ Messenger.',
      scopes: ['pages_messaging']
    }
  ];

  protected readonly expandedKey = signal<string | null>(null);
  protected readonly connecting = signal(false);

  readonly continue = output<void>();
  readonly cancelled = output<void>();

  protected toggleExpanded(key: string): void {
    this.expandedKey.set(this.expandedKey() === key ? null : key);
  }

  protected onContinue(): void {
    if (this.connecting()) {
      return;
    }
    this.connecting.set(true);
    this.continue.emit();
  }

  protected onCancel(): void {
    if (this.connecting()) {
      return;
    }
    this.cancelled.emit();
  }
}
