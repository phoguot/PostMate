import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/auth/auth.service';
import { ApiError } from '../../../core/http/api.model';

@Component({
  selector: 'app-login-page',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './login-page.html'
})
export class LoginPage {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly username = signal('');
  protected readonly password = signal('');
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal('');

  protected submit(): void {
    if (!this.username() || !this.password()) {
      this.errorMessage.set('Vui lòng nhập tên đăng nhập và mật khẩu.');
      return;
    }
    this.loading.set(true);
    this.errorMessage.set('');
    this.auth.login(this.username(), this.password()).subscribe({
      next: () => {
        this.loading.set(false);
        this.router.navigate(['/dashboard']);
      },
      error: (err: unknown) => {
        this.loading.set(false);
        this.errorMessage.set(err instanceof ApiError ? err.message : 'Đăng nhập thất bại. Vui lòng thử lại.');
      }
    });
  }
}
