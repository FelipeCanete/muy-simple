import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { httpErrorInterceptor } from '../core/http-error.interceptor';
import { DocumentNew, MAX_FILE_BYTES, MAX_SIGNERS } from './document-new';

function pdf(size = 100, name = 'contrato.pdf', type = 'application/pdf'): File {
  const file = new File(['%PDF-1.4'], name, { type });
  Object.defineProperty(file, 'size', { value: size });
  return file;
}

describe('DocumentNew', () => {
  let fixture: ComponentFixture<DocumentNew>;
  let component: DocumentNew;
  let backend: HttpTestingController;

  beforeEach(async () => {
    TestBed.configureTestingModule({
      imports: [DocumentNew],
      providers: [
        provideRouter([]),
        provideHttpClient(withInterceptors([httpErrorInterceptor])),
        provideHttpClientTesting(),
      ],
    });
    fixture = TestBed.createComponent(DocumentNew);
    component = fixture.componentInstance;
    backend = TestBed.inject(HttpTestingController);
    await fixture.whenStable();
  });

  afterEach(() => backend.verify());

  function fillValidForm(): void {
    component.form.controls.title.setValue('Contrato');
    component.signers.at(0).setValue({ email: 'ana@demo.cl', rut: '12.345.678-5' });
    component.file.set(pdf());
  }

  it('parte con un firmante y permite agregar y quitar', () => {
    expect(component.signers.length).toBe(1);

    component.addSigner();
    component.addSigner();
    expect(component.signers.length).toBe(3);

    component.removeSigner(1);
    expect(component.signers.length).toBe(2);
  });

  it('no permite quedarse sin firmantes', () => {
    component.removeSigner(0);
    expect(component.signers.length).toBe(1);
  });

  it('no permite superar el máximo de firmantes', () => {
    for (let i = 0; i < MAX_SIGNERS + 5; i++) component.addSigner();
    expect(component.signers.length).toBe(MAX_SIGNERS);
  });

  it('valida email y RUT de cada firmante', () => {
    const signer = component.signers.at(0);
    signer.setValue({ email: 'no-es-email', rut: '12345678-9' });

    expect(signer.controls['email'].hasError('email')).toBe(true);
    expect(signer.controls['rut'].hasError('rut')).toBe(true);
  });

  it('valida el archivo antes de enviarlo', () => {
    expect(component.validateFile(null)).toBe('Selecciona un archivo PDF.');
    expect(component.validateFile(pdf(100, 'foto.png', 'image/png'))).toBe('El archivo debe ser un PDF.');
    expect(component.validateFile(pdf(MAX_FILE_BYTES + 1))).toBe('El archivo supera los 10 MB.');
    expect(component.validateFile(pdf())).toBeNull();
  });

  it('no envía nada si el formulario es inválido', () => {
    component.submit();

    backend.expectNone('/api/documents');
    expect(component.fileError()).toBe('Selecciona un archivo PDF.');
  });

  it('envía el formulario como multipart y muestra los enlaces generados', async () => {
    fillValidForm();
    component.submit();

    const req = backend.expectOne('/api/documents');
    const body = req.request.body as FormData;
    expect(req.request.method).toBe('POST');
    expect(body.get('title')).toBe('Contrato');
    expect(body.get('signers[0][email]')).toBe('ana@demo.cl');
    expect(body.get('signers[0][rut]')).toBe('12.345.678-5');
    expect(body.get('file')).toBeInstanceOf(File);

    req.flush(
      {
        data: { id: 'doc-1' },
        signing_links: [{ signer_id: 's1', email: 'ana@demo.cl', url: 'http://localhost/firmar/abc' }],
      },
      { status: 201, statusText: 'Created' },
    );
    await fixture.whenStable();

    expect(component.links()).toHaveLength(1);
    const html = fixture.nativeElement as HTMLElement;
    expect(html.textContent).toContain('no se pueden volver a mostrar');
    expect((html.querySelector('.links input') as HTMLInputElement).value).toBe('http://localhost/firmar/abc');
  });

  it('muestra los errores de validación que devuelve la API', async () => {
    fillValidForm();
    component.submit();

    backend.expectOne('/api/documents').flush(
      {
        code: 'validation_failed',
        detail: 'Revisa los campos marcados.',
        errors: { file: ['El archivo debe ser un PDF válido.'] },
      },
      { status: 422, statusText: 'Unprocessable Content' },
    );
    await fixture.whenStable();

    const alert = (fixture.nativeElement as HTMLElement).querySelector('[role="alert"]');
    expect(alert?.textContent).toContain('Revisa los campos marcados.');
    expect(alert?.textContent).toContain('El archivo debe ser un PDF válido.');
    expect(component.saving()).toBe(false);
  });
});
