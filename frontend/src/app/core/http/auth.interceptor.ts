import { inject } from '@angular/core';
import { HttpEvent, HttpInterceptorFn, HttpResponse } from '@angular/common/http';
import { Router } from '@angular/router';
import { tap } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { ApiEnvelope, API_CODE } from './api.model';

const SESSION_CHECK_PATHS = ['/user/auth/me', '/user/auth/login'];
const NOT_LOGGED_IN_ERROR_CODE = 'ERR_401';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);
  const authService = inject(AuthService);

  const isSessionCheck = SESSION_CHECK_PATHS.some((path) => req.url.includes(path));
  const cloned = req.clone({ withCredentials: true });

  return next(cloned).pipe(
    tap((event: HttpEvent<unknown>) => {
      if (isSessionCheck || !(event instanceof HttpResponse)) {
        return;
      }
      const body = event.body as Partial<ApiEnvelope<unknown>> | null;
      if (body && body.code !== API_CODE.SUCCESS && body.errorCode === NOT_LOGGED_IN_ERROR_CODE) {
        authService.clearSession();
        router.navigate(['/login']);
      }
    })
  );
};
