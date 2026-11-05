import { Component, inject, signal } from '@angular/core';
import { FormArray, FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ApiError, errorMessage } from '../core/api-error';
import { SigningLink } from '../core/models';
import { normalizeRut, rutValidator } from '../shared/rut';
import { DocumentsService } from './documents.service';

export const MAX_FILE_BYTES = 10 * 1024 * 1024;
export const MAX_SIGNERS = 20;

@Component({
  selector: 'app-document-new',
  imports: [ReactiveFormsModule, RouterLink],
  template: `
    <p><a routerLink="/documentos">← Volver</a></p>
    <h1>Nuevo documento</h1>

    @if (links(); as created) {
      <section class="card">
        <p class="alert alert--success">Documento creado. Comparte cada enlace con su firmante.</p>
        <p class="alert alert--warning">
          Copia los enlaces ahora: por seguridad no se guardan y no se pueden volver a mostrar.
        </p>
        <ul class="links">
          @for (link of created; track link.signer_id) {
            <li>
              <strong>{{ link.email }}</strong>
              <input type="text" readonly [value]="link.url" (focus)="$any($event.target).select()" />
              <button type="button" class="secondary" (click)="copy(link.url)">Copiar</button>
            </li>
          }
        </ul>
        <a [routerLink]="['/documentos', createdId()]" class="button">Ver documento</a>
      </section>
    } @else {
      <form [formGroup]="form" (ngSubmit)="submit()" novalidate class="card">
        <label>
          Título
          <input type="text" formControlName="title" maxlength="150" />
          @if (form.controls.title.touched && form.controls.title.invalid) {
            <small class="field-error">Ingresa un título (3 a 150 caracteres).</small>
          }
        </label>

        <label>
          Archivo PDF (máx. 10 MB)
          <input type="file" accept="application/pdf,.pdf" (change)="onFile($event)" />
          @if (fileError()) {
            <small class="field-error">{{ fileError() }}</small>
          }
        </label>

        <fieldset>
          <legend>Firmantes</legend>
          @for (signer of signers.controls; track signer; let i = $index) {
            <div class="signer-row" [formGroup]="signer">
              <label>
                Email
                <input type="email" formControlName="email" />
                @if (signer.controls['email'].touched && signer.controls['email'].invalid) {
                  <small class="field-error">Email inválido.</small>
                }
              </label>
              <label>
                RUT
                <input type="text" formControlName="rut" placeholder="12.345.678-5" (blur)="formatRut(i)" />
                @if (signer.controls['rut'].touched && signer.controls['rut'].invalid) {
                  <small class="field-error">RUT inválido.</small>
                }
              </label>
              <button type="button" class="secondary" (click)="removeSigner(i)" [disabled]="signers.length === 1" aria-label="Quitar firmante">
                Quitar
              </button>
            </div>
          }
          <button type="button" class="secondary" (click)="addSigner()" [disabled]="signers.length >= maxSigners">
            + Agregar firmante
          </button>
        </fieldset>

        @if (serverError(); as err) {
          <div class="alert alert--error" role="alert">
            <p>{{ err.detail }}</p>
            @if (err.messages.length) {
              <ul>
                @for (msg of err.messages; track $index) {
                  <li>{{ msg }}</li>
                }
              </ul>
            }
          </div>
        } @else if (genericError()) {
          <p class="alert alert--error" role="alert">{{ genericError() }}</p>
        }

        <button type="submit" [disabled]="saving()">{{ saving() ? 'Subiendo…' : 'Crear y generar enlaces' }}</button>
      </form>
    }
  `,
})
export class DocumentNew {
  private readonly fb = inject(FormBuilder).nonNullable;
  private readonly documents = inject(DocumentsService);

  readonly maxSigners = MAX_SIGNERS;

  readonly form = this.fb.group({
    title: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(150)]],
    signers: this.fb.array<FormGroup>([this.newSigner()]),
  });

  readonly file = signal<File | null>(null);
  readonly fileError = signal<string | null>(null);
  readonly saving = signal(false);
  readonly serverError = signal<ApiError | null>(null);
  readonly genericError = signal<string | null>(null);
  readonly links = signal<SigningLink[] | null>(null);
  readonly createdId = signal<string | null>(null);

  get signers(): FormArray<FormGroup> {
    return this.form.controls.signers;
  }

  addSigner(): void {
    if (this.signers.length < MAX_SIGNERS) {
      this.signers.push(this.newSigner());
    }
  }

  removeSigner(index: number): void {
    if (this.signers.length > 1) {
      this.signers.removeAt(index);
    }
  }

  formatRut(index: number): void {
    const control = this.signers.at(index).controls['rut'];
    if (control.valid && control.value) {
      control.setValue(normalizeRut(control.value));
    }
  }

  onFile(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    this.file.set(null);
    this.fileError.set(this.validateFile(file));
    if (file && !this.fileError()) {
      this.file.set(file);
    }
  }

  validateFile(file: File | null): string | null {
    if (!file) return 'Selecciona un archivo PDF.';
    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
      return 'El archivo debe ser un PDF.';
    }
    if (file.size > MAX_FILE_BYTES) return 'El archivo supera los 10 MB.';
    return null;
  }

  submit(): void {
    this.form.markAllAsTouched();
    const file = this.file();
    if (!file) {
      this.fileError.set(this.validateFile(null));
    }
    if (this.form.invalid || !file) return;

    this.saving.set(true);
    this.serverError.set(null);
    this.genericError.set(null);

    const { title, signers } = this.form.getRawValue();

    this.documents.create(title.trim(), file, signers as { email: string; rut: string }[]).subscribe({
      next: (res) => {
        this.links.set(res.signing_links);
        this.createdId.set(res.data.id);
        this.saving.set(false);
      },
      error: (e: unknown) => {
        if (e instanceof ApiError) {
          this.serverError.set(e);
        } else {
          this.genericError.set(errorMessage(e));
        }
        this.saving.set(false);
      },
    });
  }

  copy(url: string): void {
    void navigator.clipboard?.writeText(url);
  }

  private newSigner(): FormGroup {
    return this.fb.group({
      email: ['', [Validators.required, Validators.email, Validators.maxLength(254)]],
      rut: ['', [Validators.required, rutValidator]],
    });
  }
}
