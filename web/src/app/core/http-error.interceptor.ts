import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { ApiError } from './api-error';
import { AuthService } from './auth.service';

/**
 * Traduce cualquier error HTTP a ApiError, para que los componentes
 * trabajen con un único tipo. Si la sesión del panel expiró (401),
 * limpia el estado y manda al login.
 */
export const httpErrorInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);
  const auth = inject(AuthService);

  return next(req).pipe(
    catchError((error: unknown) => {
      if (!(error instanceof HttpErrorResponse)) {
        return throwError(() => error);
      }

      const apiError = ApiError.fromHttpError(error);

      const isPanelRequest =
        !req.url.includes('/api/login') && !req.url.includes('/api/me') && !req.url.includes('/api/sign/');

      if (apiError.status === 401 && isPanelRequest) {
        auth.clear();
        void router.navigate(['/login']);
      }

      return throwError(() => apiError);
    }),
  );
};
