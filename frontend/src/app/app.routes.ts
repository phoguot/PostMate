import { Routes } from '@angular/router';
import { authGuard, guestGuard } from './core/auth/auth.guard';

export const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
  {
    path: 'login',
    canActivate: [guestGuard],
    loadComponent: () => import('./features/auth/login-page/login-page').then((m) => m.LoginPage)
  },
  {
    path: '',
    canActivate: [authGuard],
    loadComponent: () => import('./layout/app-shell/app-shell').then((m) => m.AppShell),
    children: [
      {
        path: 'dashboard',
        loadComponent: () => import('./features/dashboard/dashboard-page').then((m) => m.DashboardPage)
      },
      {
        path: 'posts',
        loadComponent: () => import('./features/posting/post-list-page/post-list-page').then((m) => m.PostListPage)
      },
      {
        path: 'schedule',
        loadComponent: () => import('./features/posting/schedule-page/schedule-page').then((m) => m.SchedulePage)
      },
      {
        path: 'create-post',
        loadComponent: () => import('./features/posting/create-post-page/create-post-page').then((m) => m.CreatePostPage)
      },
      {
        path: 'accounts',
        loadComponent: () => import('./features/facebook/account-page/account-page').then((m) => m.AccountPage)
      },
      {
        path: 'fanpages',
        loadComponent: () => import('./features/facebook/fanpage-page/fanpage-page').then((m) => m.FanpagePage)
      },
      {
        path: 'cookies',
        loadComponent: () => import('./features/facebook/cookie-page/cookie-page').then((m) => m.CookiePage)
      },
      {
        path: 'browsers',
        loadComponent: () =>
          import('./features/infra/browser-profile-page/browser-profile-page').then((m) => m.BrowserProfilePage)
      },
      {
        path: 'settings',
        loadComponent: () => import('./features/setting/setting-page/setting-page').then((m) => m.SettingPage)
      },
      {
        path: 'activity-log',
        loadComponent: () => import('./shared/ui/placeholder-page/placeholder-page').then((m) => m.PlaceholderPage),
        data: {
          title: 'Nhật ký hoạt động',
          subtitle: 'Lịch sử hoạt động của hệ thống',
          message: 'Chưa có API nhật ký hoạt động — sẽ được bổ sung sau.'
        }
      },
      {
        path: 'ai-agent',
        loadComponent: () => import('./shared/ui/placeholder-page/placeholder-page').then((m) => m.PlaceholderPage),
        data: {
          title: 'AI Agent',
          subtitle: 'Quản lý AI Agent hỗ trợ tạo nội dung',
          message: 'Chưa có module AI Agent trên backend — sẽ được bổ sung sau.'
        }
      }
    ]
  },
  { path: '**', redirectTo: 'dashboard' }
];
