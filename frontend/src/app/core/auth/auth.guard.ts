import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { map, of } from 'rxjs';
import { AuthService } from './auth.service';

export const authGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (auth.isAuthenticated()) {
    return of(true);
  }

  if (auth.checked()) {
    return of(router.createUrlTree(['/login']));
  }

  return auth.fetchMe().pipe(map((user) => (user ? true : router.createUrlTree(['/login']))));
};

export const guestGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (auth.isAuthenticated()) {
    return of(router.createUrlTree(['/dashboard']));
  }

  if (auth.checked()) {
    return of(true);
  }

  return auth.fetchMe().pipe(map((user) => (user ? router.createUrlTree(['/dashboard']) : true)));
};
