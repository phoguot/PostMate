import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from '../../../core/http/api.service';
import { PaginatedResult } from '../../../core/http/api.model';
import { ListPayload } from '../../facebook/shared/facebook-api.service';
import { BrowserProfile } from './browser-profile.model';

export interface BrowserProfileStats {
  total: number;
  serverCount: number;
  running: number;
  stopped: number;
  offline: number;
}

@Injectable({ providedIn: 'root' })
export class BrowserProfileApiService {
  private readonly api = inject(ApiService);

  list(payload: ListPayload = {}): Observable<PaginatedResult<BrowserProfile>> {
    return this.api.post<PaginatedResult<BrowserProfile>>('/infra/browser-profile/index', payload);
  }

  stats(): Observable<BrowserProfileStats> {
    return this.api.post<BrowserProfileStats>('/infra/browser-profile/stats', {});
  }

  detail(id: number): Observable<BrowserProfile> {
    return this.api.post<BrowserProfile>('/infra/browser-profile/detail', { id });
  }

  start(id: number): Observable<unknown> {
    return this.api.post('/infra/browser-profile/start', { id });
  }

  stop(id: number): Observable<unknown> {
    return this.api.post('/infra/browser-profile/stop', { id });
  }

  restart(id: number): Observable<unknown> {
    return this.api.post('/infra/browser-profile/restart', { id });
  }

  open(id: number): Observable<unknown> {
    return this.api.post('/infra/browser-profile/open', { id });
  }

  delete(id: number): Observable<unknown> {
    return this.api.post('/infra/browser-profile/delete', { id });
  }
}
