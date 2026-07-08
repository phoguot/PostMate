import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { LucideEye, LucideEyeOff, LucidePencil, LucideShieldCheck } from '@lucide/angular';
import { AppIcon } from '../../../../../shared/ui/icon/app-icon';
import { AuthService } from '../../../../../core/auth/auth.service';
import { ProfileApiService } from '../../../../../core/auth/profile-api.service';
import { NotifyService } from '../../../../../core/notify/notify.service';
import { ApiError } from '../../../../../core/http/api.model';

@Component({
  selector: 'app-account-panel',
  standalone: true,
  imports: [FormsModule, AppIcon],
  templateUrl: './account-panel.html'
})
export class AccountPanel {
  private readonly profileApi = inject(ProfileApiService);
  private readonly notify = inject(NotifyService);
  protected readonly auth = inject(AuthService);

  protected readonly editIcon = LucidePencil;
  protected readonly shieldIcon = LucideShieldCheck;
  protected readonly eyeIcon = LucideEye;
  protected readonly eyeOffIcon = LucideEyeOff;

  protected readonly editing = signal(false);
  protected readonly fullName = signal(this.auth.currentUser()?.fullName ?? '');

  protected readonly currentPassword = signal('');
  protected readonly newPassword = signal('');
  protected readonly confirmPassword = signal('');
  protected readonly showCurrentPassword = signal(false);
  protected readonly showNewPassword = signal(false);
  protected readonly showConfirmPassword = signal(false);
  protected readonly changingPassword = signal(false);

  protected readonly twoFactorEnabled = signal(false);

  protected startEdit(): void {
    this.fullName.set(this.auth.currentUser()?.fullName ?? '');
    this.editing.set(true);
  }

  protected cancelEdit(): void {
    this.editing.set(false);
  }

  protected saveProfile(): void {
    this.profileApi.updateProfile({ fullName: this.fullName() }).subscribe({
      next: () => {
        this.notify.success('Đã cập nhật hồ sơ.');
        this.editing.set(false);
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể cập nhật hồ sơ.')
    });
  }

  protected changePassword(): void {
    if (this.changingPassword()) {
      return;
    }
    this.changingPassword.set(true);
    this.profileApi
      .changePassword({
        currentPassword: this.currentPassword(),
        newPassword: this.newPassword(),
        confirmPassword: this.confirmPassword()
      })
      .subscribe({
        next: () => {
          this.changingPassword.set(false);
          this.notify.success('Đã đổi mật khẩu.');
          this.currentPassword.set('');
          this.newPassword.set('');
          this.confirmPassword.set('');
        },
        error: (err: unknown) => {
          this.changingPassword.set(false);
          this.notify.error(err instanceof ApiError ? err.message : 'Đổi mật khẩu thất bại.');
        }
      });
  }

  protected toggleTwoFactor(): void {
    const next = !this.twoFactorEnabled();
    this.profileApi.toggleTwoFactor(next).subscribe({
      next: (res) => {
        this.twoFactorEnabled.set(res.twoFactorEnabled);
        this.notify.success(res.twoFactorEnabled ? 'Đã bật xác thực 2 lớp.' : 'Đã tắt xác thực 2 lớp.');
      },
      error: (err: unknown) => this.notify.error(err instanceof ApiError ? err.message : 'Không thể cập nhật 2FA.')
    });
  }
}
