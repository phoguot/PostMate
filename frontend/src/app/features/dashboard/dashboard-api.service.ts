import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from '../../core/http/api.service';
import { Post } from '../posting/shared/post.model';
import { DashboardDistribution, DashboardHealth, DashboardOverview, DashboardPerformancePoint } from './dashboard.model';

export interface DashboardRangePayload {
  fromDate?: string;
  toDate?: string;
}

@Injectable({ providedIn: 'root' })
export class DashboardApiService {
  private readonly api = inject(ApiService);

  overview(payload: DashboardRangePayload): Observable<DashboardOverview> {
    return this.api.post<DashboardOverview>('/posting/dashboard/overview', payload);
  }

  performance(payload: DashboardRangePayload): Observable<DashboardPerformancePoint[]> {
    return this.api.post<DashboardPerformancePoint[]>('/posting/dashboard/performance', payload);
  }

  distribution(payload: DashboardRangePayload): Observable<DashboardDistribution> {
    return this.api.post<DashboardDistribution>('/posting/dashboard/distribution', payload);
  }

  recent(limit = 5): Observable<Post[]> {
    return this.api.post<Post[]>('/posting/dashboard/recent', { limit });
  }

  health(): Observable<DashboardHealth> {
    return this.api.post<DashboardHealth>('/posting/dashboard/health', {});
  }
}
