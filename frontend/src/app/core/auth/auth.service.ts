import { Injectable, computed, inject, signal } from '@angular/core';
import { Observable, of } from 'rxjs';
import { catchError, map, tap } from 'rxjs/operators';
import { ApiService } from '../http/api.service';

export interface CurrentUser {
  id: number;
  username: string;
  email?: string;
  fullName?: string;
  role?: string;
  avatarUrl?: string;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);

  private readonly _currentUser = signal<CurrentUser | null>(null);
  private readonly _checked = signal(false);

  readonly currentUser = this._currentUser.asReadonly();
  readonly checked = this._checked.asReadonly();
  readonly isAuthenticated = computed(() => this._currentUser() !== null);

  login(username: string, password: string): Observable<CurrentUser> {
    return this.api.post<CurrentUser>('/user/auth/login', { username, password }).pipe(
      tap((user) => {
        this._currentUser.set(user);
        this._checked.set(true);
      })
    );
  }

  logout(): Observable<void> {
    return this.api.post<void>('/user/auth/logout').pipe(
      tap(() => this._currentUser.set(null)),
      map(() => undefined)
    );
  }

  fetchMe(): Observable<CurrentUser | null> {
    return this.api.post<CurrentUser>('/user/auth/me').pipe(
      tap((user) => {
        this._currentUser.set(user);
        this._checked.set(true);
      }),
      catchError(() => {
        this._currentUser.set(null);
        this._checked.set(true);
        return of(null);
      })
    );
  }

  clearSession(): void {
    this._currentUser.set(null);
    this._checked.set(true);
  }
}
