import { TestBed } from '@angular/core/testing';
import { ActivatedRouteSnapshot, RouterStateSnapshot, UrlTree, provideRouter } from '@angular/router';
import { authGuard, guestGuard } from './auth.guard';
import { AuthService } from './auth.service';

describe('guards', () => {
  let ensureLoaded: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    ensureLoaded = vi.fn();
    TestBed.configureTestingModule({
      providers: [provideRouter([]), { provide: AuthService, useValue: { ensureLoaded } }],
    });
  });

  const run = (guard: typeof authGuard) =>
    TestBed.runInInjectionContext(() => guard({} as ActivatedRouteSnapshot, {} as RouterStateSnapshot));

  it('authGuard deja pasar con sesión', async () => {
    ensureLoaded.mockResolvedValue({ id: 1, name: 'A', email: 'a@demo.cl' });
    expect(await run(authGuard)).toBe(true);
  });

  it('authGuard manda al login sin sesión', async () => {
    ensureLoaded.mockResolvedValue(null);
    const result = (await run(authGuard)) as UrlTree;
    expect(result.toString()).toBe('/login');
  });

  it('guestGuard saca del login a quien ya tiene sesión', async () => {
    ensureLoaded.mockResolvedValue({ id: 1, name: 'A', email: 'a@demo.cl' });
    const result = (await run(guestGuard)) as UrlTree;
    expect(result.toString()).toBe('/documentos');
  });
});
