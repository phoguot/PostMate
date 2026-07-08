import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from '../../../core/http/api.service';
import { PaginatedResult } from '../../../core/http/api.model';
import { FacebookAccount, Fanpage, Cookie } from './facebook.model';

export interface ListPayload {
  page?: number;
  pageSize?: number;
  keyword?: string;
  status?: number;
  [key: string]: unknown;
}

export interface FacebookAccountStats {
  total: number;
  active: number;
  inactive: number;
  checkpoint: number;
  expired: number;
  expiringCookie: number;
}

export interface FanpageStats {
  total: number;
  active: number;
  needRelogin: number;
  inactive: number;
}

export interface CookieStats {
  total: number;
  valid: number;
  expiring: number;
  invalid: number;
}

@Injectable({ providedIn: 'root' })
export class FacebookAccountApiService {
  private readonly api = inject(ApiService);

  list(payload: ListPayload = {}): Observable<PaginatedResult<FacebookAccount>> {
    return this.api.post<PaginatedResult<FacebookAccount>>('/facebook/account/index', payload);
  }

  stats(): Observable<FacebookAccountStats> {
    return this.api.post<FacebookAccountStats>('/facebook/account/stats', {});
  }

  detail(id: number): Observable<FacebookAccount> {
    return this.api.post<FacebookAccount>('/facebook/account/detail', { id });
  }

  /** Trả về URL cấp quyền OAuth Facebook thật — FE điều hướng trình duyệt sang URL này. */
  connect(): Observable<{ authorizeUrl: string }> {
    return this.api.post<{ authorizeUrl: string }>('/facebook/account/connect', {});
  }

  reLogin(id: number): Observable<unknown> {
    return this.api.post('/facebook/account/relogin', { id });
  }

  delete(id: number): Observable<unknown> {
    return this.api.post('/facebook/account/delete', { id });
  }
}

@Injectable({ providedIn: 'root' })
export class FanpageApiService {
  private readonly api = inject(ApiService);

  list(payload: ListPayload = {}): Observable<PaginatedResult<Fanpage>> {
    return this.api.post<PaginatedResult<Fanpage>>('/facebook/fanpage/index', payload);
  }

  stats(): Observable<FanpageStats> {
    return this.api.post<FanpageStats>('/facebook/fanpage/stats', {});
  }

  detail(id: number): Observable<Fanpage> {
    return this.api.post<Fanpage>('/facebook/fanpage/detail', { id });
  }

  reLogin(id: number): Observable<unknown> {
    return this.api.post('/facebook/fanpage/relogin', { id });
  }

  unlink(id: number): Observable<unknown> {
    return this.api.post('/facebook/fanpage/unlink', { id });
  }
}

@Injectable({ providedIn: 'root' })
export class CookieApiService {
  private readonly api = inject(ApiService);

  list(payload: ListPayload = {}): Observable<PaginatedResult<Cookie>> {
    return this.api.post<PaginatedResult<Cookie>>('/facebook/cookie/index', payload);
  }

  stats(): Observable<CookieStats> {
    return this.api.post<CookieStats>('/facebook/cookie/stats', {});
  }

  detail(id: number): Observable<Cookie> {
    return this.api.post<Cookie>('/facebook/cookie/detail', { id });
  }

  login(id: number): Observable<unknown> {
    return this.api.post('/facebook/cookie/login', { id });
  }

  refresh(id: number): Observable<unknown> {
    return this.api.post('/facebook/cookie/refresh', { id });
  }

  refreshAll(): Observable<unknown> {
    return this.api.post('/facebook/cookie/refreshall', {});
  }

  export(id: number): Observable<unknown> {
    return this.api.post('/facebook/cookie/export', { id });
  }

  delete(id: number): Observable<unknown> {
    return this.api.post('/facebook/cookie/delete', { id });
  }
}
