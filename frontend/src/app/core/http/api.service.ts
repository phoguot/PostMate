import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { ApiEnvelope, ApiError, API_CODE } from './api.model';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);

  post<T>(path: string, body: object = {}): Observable<T> {
    return this.http.post<ApiEnvelope<T>>(`/api${path}`, body).pipe(
      map((envelope) => {
        if (envelope.code !== API_CODE.SUCCESS) {
          throw new ApiError(envelope.messages?.[0] ?? 'Đã có lỗi xảy ra', envelope.code, envelope.errorCode);
        }
        return envelope.data;
      })
    );
  }
}
