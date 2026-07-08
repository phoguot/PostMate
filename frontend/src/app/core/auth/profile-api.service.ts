import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from '../http/api.service';
import { CurrentUser } from './auth.service';

export interface ProfileUpdatePayload {
  fullName?: string;
  avatarUrl?: string;
}

export interface ChangePasswordPayload {
  currentPassword: string;
  newPassword: string;
  confirmPassword: string;
}

@Injectable({ providedIn: 'root' })
export class ProfileApiService {
  private readonly api = inject(ApiService);

  updateProfile(payload: ProfileUpdatePayload): Observable<CurrentUser> {
    return this.api.post<CurrentUser>('/user/profile/update', payload as Record<string, unknown>);
  }

  changePassword(payload: ChangePasswordPayload): Observable<unknown> {
    return this.api.post('/user/profile/changePassword', payload as unknown as Record<string, unknown>);
  }

  toggleTwoFactor(enabled: boolean): Observable<{ twoFactorEnabled: boolean }> {
    return this.api.post<{ twoFactorEnabled: boolean }>('/user/profile/twoFactor', { enabled });
  }
}
