import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { Router, provideRouter } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ApiError } from './api-error';
import { AuthService } from './auth.service';
import { httpErrorInterceptor } from './http-error.interceptor';

describe('httpErrorInterceptor', () => {
  let http: HttpClient;
  let backend: HttpTestingController;
  let router: Router;
  let auth: AuthService;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideRouter([]),
        provideHttpClient(withInterceptors([httpErrorInterceptor])),
        provideHttpClientTesting(),
      ],
    });
    http = TestBed.inject(HttpClient);
    backend = TestBed.inject(HttpTestingController);
    router = TestBed.inject(Router);
    auth = TestBed.inject(AuthService);
    vi.spyOn(router, 'navigate').mockResolvedValue(true);
  });

  afterEach(() => backend.verify());

  async function failWith(url: string, status: number, body: string | object): Promise<ApiError> {
    const request = firstValueFrom(http.get(url));
    backend.expectOne(url).flush(body, { status, statusText: 'Error' });
    try {
      await request;
    } catch (e) {
      return e as ApiError;
    }
    throw new Error('Se esperaba un error');
  }

  it('convierte una respuesta problem+json en ApiError', async () => {
    const error = await failWith('/api/sign/abc', 410, {
      status: 410,
      code: 'token_expired',
      title: 'Enlace de firma expirado',
      detail: 'El enlace de firma expiró.',
    });

    expect(error).toBeInstanceOf(ApiError);
    expect(error.status).toBe(410);
    expect(error.code).toBe('token_expired');
    expect(error.detail).toBe('El enlace de firma expiró.');
  });

  it('expone los errores de validación por campo', async () => {
    const error = await failWith('/api/documents', 422, {
      code: 'validation_failed',
      detail: 'Revisa los campos marcados.',
      errors: { 'signers.0.rut': ['El RUT no es válido.'], file: ['El archivo debe ser un PDF válido.'] },
    });

    expect(error.fieldErrors['signers.0.rut']).toEqual(['El RUT no es válido.']);
    expect(error.messages).toEqual(['El RUT no es válido.', 'El archivo debe ser un PDF válido.']);
  });

  it('usa un mensaje genérico si la respuesta no es problem+json', async () => {
    const error = await failWith('/api/documents', 502, '<html>Bad gateway</html>');

    expect(error.code).toBe('http_error');
    expect(error.detail).toContain('error inesperado');
  });

  it('informa cuando no hay conexión', async () => {
    const request = firstValueFrom(http.get('/api/documents'));
    backend.expectOne('/api/documents').error(new ProgressEvent('error'), { status: 0 });

    await expect(request).rejects.toMatchObject({ code: 'network_error' });
  });

  it('con 401 en el panel, limpia la sesión y redirige al login', async () => {
    const clear = vi.spyOn(auth, 'clear');

    await failWith('/api/documents', 401, { code: 'unauthenticated', detail: 'Debes iniciar sesión.' });

    expect(clear).toHaveBeenCalled();
    expect(router.navigate).toHaveBeenCalledWith(['/login']);
  });

  it('no redirige con 401 en la página pública de firma', async () => {
    await failWith('/api/sign/abc', 401, { code: 'unauthenticated' });

    expect(router.navigate).not.toHaveBeenCalled();
  });
});
