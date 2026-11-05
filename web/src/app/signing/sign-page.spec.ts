import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { httpErrorInterceptor } from '../core/http-error.interceptor';
import { SigningPage } from '../core/models';
import { SignPage } from './sign-page';

const TOKEN = 'a'.repeat(64);

function page(signerStatus: 'pendiente' | 'firmado' = 'pendiente'): { data: SigningPage } {
  return {
    data: {
      document: {
        title: 'Contrato de arriendo',
        original_name: 'contrato.pdf',
        size: 1000,
        sha256: 'abc',
        status: signerStatus === 'firmado' ? 'completo' : 'pendiente',
        status_label: '',
      },
      signer: {
        email: 'ana@demo.cl',
        status: signerStatus,
        signed_at: signerStatus === 'firmado' ? '2026-11-05T10:00:00Z' : null,
        expires_at: '2026-11-12T10:00:00Z',
      },
    },
  };
}

describe('SignPage', () => {
  let fixture: ComponentFixture<SignPage>;
  let backend: HttpTestingController;

  beforeEach(async () => {
    TestBed.configureTestingModule({
      imports: [SignPage],
      providers: [
        provideRouter([]),
        provideHttpClient(withInterceptors([httpErrorInterceptor])),
        provideHttpClientTesting(),
      ],
    });
    fixture = TestBed.createComponent(SignPage);
    fixture.componentRef.setInput('token', TOKEN);
    backend = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });

  afterEach(() => backend.verify());

  const text = () => (fixture.nativeElement as HTMLElement).textContent ?? '';

  async function respondShow(body: object, status = 200): Promise<void> {
    backend.expectOne(`/api/sign/${TOKEN}`).flush(body, { status, statusText: 'x' });
    await fixture.whenStable();
  }

  it('muestra el documento y el botón de firma deshabilitado hasta aceptar', async () => {
    await respondShow(page());

    expect(text()).toContain('Contrato de arriendo');
    expect(text()).toContain('ana@demo.cl');
    const button = (fixture.nativeElement as HTMLElement).querySelector('button') as HTMLButtonElement;
    expect(button.disabled).toBe(true);

    fixture.componentInstance.accepted.set(true);
    await fixture.whenStable();
    expect(button.disabled).toBe(false);
  });

  it.each([
    [404, 'invalid_token', 'Enlace no válido'],
    [410, 'token_expired', 'Enlace expirado'],
    [429, 'too_many_requests', 'Demasiados intentos'],
  ])('con HTTP %i (%s) muestra "%s"', async (status, code, title) => {
    await respondShow({ status, code, detail: 'x' }, status);

    expect(text()).toContain(title);
    expect((fixture.nativeElement as HTMLElement).querySelector('button')).toBeNull();
  });

  it('indica cuando el firmante ya firmó', async () => {
    await respondShow(page('firmado'));

    expect(text()).toContain('Ya firmaste este documento');
    expect((fixture.nativeElement as HTMLElement).querySelector('button')).toBeNull();
  });

  it('firma (pidiendo antes la cookie CSRF) y muestra la confirmación', async () => {
    await respondShow(page());

    fixture.componentInstance.accepted.set(true);
    fixture.componentInstance.sign();

    backend.expectOne('/sanctum/csrf-cookie').flush(null, { status: 204, statusText: 'No Content' });
    const req = backend.expectOne(`/api/sign/${TOKEN}`);
    expect(req.request.method).toBe('POST');
    req.flush(page('firmado'));
    await fixture.whenStable();

    expect(text()).toContain('Ya firmaste este documento');
  });

  it('si otro canal ya registró la firma (409), recarga el estado real', async () => {
    await respondShow(page());

    fixture.componentInstance.sign();
    backend.expectOne('/sanctum/csrf-cookie').flush(null, { status: 204, statusText: 'No Content' });
    backend
      .expectOne((r) => r.method === 'POST')
      .flush({ status: 409, code: 'already_signed', detail: 'Ya firmó' }, { status: 409, statusText: 'Conflict' });

    await respondShow(page('firmado'));
    expect(text()).toContain('Ya firmaste este documento');
  });

  it('muestra un error recuperable si la firma falla por otro motivo', async () => {
    await respondShow(page());

    fixture.componentInstance.sign();
    backend.expectOne('/sanctum/csrf-cookie').flush(null, { status: 204, statusText: 'No Content' });
    backend
      .expectOne((r) => r.method === 'POST')
      .flush({ status: 500, code: 'internal_error', detail: 'Ocurrió un error inesperado.' }, { status: 500, statusText: 'Error' });
    await fixture.whenStable();

    expect(text()).toContain('Ocurrió un error inesperado.');
    // Sigue en la pantalla de firma para poder reintentar.
    expect(text()).toContain('Firmar documento');
  });
});
