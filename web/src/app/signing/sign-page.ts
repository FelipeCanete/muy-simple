import { DatePipe } from '@angular/common';
import { Component, computed, inject, input, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiError } from '../core/api-error';
import { SigningPage } from '../core/models';
import { SigningService } from './signing.service';

/** Mensajes según el código de error que devuelve la API. */
const ERROR_MESSAGES: Record<string, { title: string; text: string }> = {
  invalid_token: {
    title: 'Enlace no válido',
    text: 'Este enlace de firma no existe. Revisa que lo hayas copiado completo.',
  },
  not_found: {
    title: 'Enlace no válido',
    text: 'Este enlace de firma no existe. Revisa que lo hayas copiado completo.',
  },
  token_expired: {
    title: 'Enlace expirado',
    text: 'El plazo para firmar con este enlace terminó. Pide uno nuevo a quien te envió el documento.',
  },
  too_many_requests: {
    title: 'Demasiados intentos',
    text: 'Espera un minuto y vuelve a intentarlo.',
  },
};

@Component({
  selector: 'app-sign-page',
  imports: [DatePipe, FormsModule],
  template: `
    <section class="card card--narrow">
      @if (loading()) {
        <p class="muted">Cargando documento…</p>
      } @else if (errorView(); as err) {
        <h1>{{ err.title }}</h1>
        <p class="alert alert--error" role="alert">{{ err.text }}</p>
      } @else if (page(); as p) {
        <h1>{{ p.document.title }}</h1>
        <p class="muted">Firmante: {{ p.signer.email }}</p>
        <p>
          <a [href]="fileUrl()" target="_blank" rel="noopener noreferrer">Ver documento ({{ p.document.original_name }})</a>
        </p>

        @if (p.signer.status === 'firmado') {
          <p class="alert alert--success">
            Ya firmaste este documento el {{ p.signer.signed_at | date: 'medium' }}. ¡Gracias!
          </p>
        } @else {
          <label class="checkbox">
            <input type="checkbox" [(ngModel)]="accepted" />
            Leí el documento y acepto firmarlo.
          </label>
          <button type="button" (click)="sign()" [disabled]="!accepted() || signing()">
            {{ signing() ? 'Firmando…' : 'Firmar documento' }}
          </button>
          <p class="muted small">El enlace vence el {{ p.signer.expires_at | date: 'medium' }}.</p>
        }

        @if (signError()) {
          <p class="alert alert--error" role="alert">{{ signError() }}</p>
        }
      }
    </section>
  `,
})
export class SignPage implements OnInit {
  private readonly signing$ = inject(SigningService);

  /** Viene del parámetro de ruta :token. */
  readonly token = input.required<string>();

  readonly page = signal<SigningPage | null>(null);
  readonly loading = signal(true);
  readonly error = signal<ApiError | null>(null);
  readonly signError = signal<string | null>(null);
  readonly signing = signal(false);
  readonly accepted = signal(false);

  readonly fileUrl = computed(() => this.signing$.fileUrl(this.token()));

  readonly errorView = computed(() => {
    const err = this.error();
    if (!err) return null;
    return ERROR_MESSAGES[err.code] ?? { title: 'No pudimos cargar el documento', text: err.detail };
  });

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.signing$.get(this.token()).subscribe({
      next: (res) => {
        this.page.set(res.data);
        this.loading.set(false);
      },
      error: (e: unknown) => {
        this.error.set(
          e instanceof ApiError ? e : new ApiError(0, 'unknown', 'Error', 'No pudimos cargar el documento.'),
        );
        this.loading.set(false);
      },
    });
  }

  sign(): void {
    this.signing.set(true);
    this.signError.set(null);

    this.signing$.sign(this.token()).subscribe({
      next: (res) => {
        this.page.set(res.data);
        this.signing.set(false);
      },
      error: (e: unknown) => {
        this.signing.set(false);
        if (e instanceof ApiError && e.code === 'already_signed') {
          // Alguien (o el webhook) ya registró la firma: recargamos el estado real.
          this.load();
          return;
        }
        if (e instanceof ApiError && ERROR_MESSAGES[e.code]) {
          this.error.set(e);
          return;
        }
        this.signError.set(e instanceof ApiError ? e.detail : 'No se pudo registrar la firma. Inténtalo de nuevo.');
      },
    });
  }
}
