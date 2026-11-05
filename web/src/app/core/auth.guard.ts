import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from './auth.service';

/** Protege las rutas del panel. La API valida igual: esto es solo UX. */
export const authGuard: CanActivateFn = async () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  const user = await auth.ensureLoaded();
  return user ? true : router.createUrlTree(['/login']);
};

/** Evita mostrar el login a quien ya tiene sesión. */
export const guestGuard: CanActivateFn = async () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  const user = await auth.ensureLoaded();
  return user ? router.createUrlTree(['/documentos']) : true;
};
