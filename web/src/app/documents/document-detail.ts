import { DatePipe } from '@angular/common';
import { Component, inject, input, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { errorMessage } from '../core/api-error';
import { SignDocument } from '../core/models';
import { StatusBadge } from '../shared/status-badge';
import { DocumentsService } from './documents.service';

@Component({
  selector: 'app-document-detail',
  imports: [RouterLink, DatePipe, StatusBadge],
  template: `
    <p><a routerLink="/documentos">← Volver</a></p>

    @if (error()) {
      <p class="alert alert--error" role="alert">{{ error() }}</p>
    } @else if (doc(); as d) {
      <header class="page-header">
        <h1>{{ d.title }}</h1>
        <app-status-badge [status]="d.status" />
      </header>

      <p class="muted">
        {{ d.original_name }} · {{ d.signers_signed }}/{{ d.signers_total }} firmas ·
        <!-- Sin "noreferrer": Sanctum necesita el Referer del panel para usar la sesión. -->
        <a [href]="fileUrl(d.id)" target="_blank" rel="noopener">Ver PDF</a>
      </p>
      <p class="muted small">SHA-256: <code>{{ d.sha256 }}</code></p>

      <h2>Firmantes</h2>
      <table>
        <thead>
          <tr><th>Email</th><th>RUT</th><th>Estado</th><th>Firmado</th><th>ID (para webhook)</th></tr>
        </thead>
        <tbody>
          @for (s of d.signers ?? []; track s.id) {
            <tr>
              <td>{{ s.email }}</td>
              <td>{{ s.rut }}</td>
              <td>{{ s.status === 'firmado' ? 'Firmado' : 'Pendiente' }}</td>
              <td>
                @if (s.signed_at) {
                  {{ s.signed_at | date: 'short' }} ({{ s.signed_via === 'webhook' ? 'webhook' : 'enlace' }})
                } @else {
                  —
                }
              </td>
              <td><code class="small">{{ s.id }}</code></td>
            </tr>
          }
        </tbody>
      </table>
    } @else {
      <p class="muted">Cargando…</p>
    }
  `,
})
export class DocumentDetail implements OnInit {
  private readonly documents = inject(DocumentsService);

  /** Viene del parámetro de ruta :id (withComponentInputBinding). */
  readonly id = input.required<string>();

  readonly doc = signal<SignDocument | null>(null);
  readonly error = signal<string | null>(null);

  ngOnInit(): void {
    this.documents.get(this.id()).subscribe({
      next: (res) => this.doc.set(res.data),
      error: (e: unknown) => this.error.set(errorMessage(e)),
    });
  }

  fileUrl(id: string): string {
    return this.documents.fileUrl(id);
  }
}
