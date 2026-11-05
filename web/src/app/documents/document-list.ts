import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { errorMessage } from '../core/api-error';
import { DOCUMENT_STATUS_LABELS, DocumentStatus, Paginated, SignDocument } from '../core/models';
import { StatusBadge } from '../shared/status-badge';
import { DocumentsService } from './documents.service';

@Component({
  selector: 'app-document-list',
  imports: [RouterLink, DatePipe, StatusBadge],
  template: `
    <header class="page-header">
      <h1>Documentos</h1>
      <a routerLink="/documentos/nuevo" class="button">Nuevo documento</a>
    </header>

    <label class="inline">
      Estado
      <select [value]="status()" (change)="filter($any($event.target).value)">
        <option value="">Todos</option>
        @for (option of statusOptions; track option.value) {
          <option [value]="option.value">{{ option.label }}</option>
        }
      </select>
    </label>

    @if (error()) {
      <p class="alert alert--error" role="alert">
        {{ error() }} <button type="button" class="link" (click)="load()">Reintentar</button>
      </p>
    } @else if (loading()) {
      <p class="muted">Cargando…</p>
    } @else if (page(); as p) {
      @if (p.data.length === 0) {
        <p class="muted">No hay documentos{{ status() ? ' con ese estado' : '' }}.</p>
      } @else {
        <table>
          <thead>
            <tr><th>Título</th><th>Estado</th><th>Firmas</th><th>Creado</th><th></th></tr>
          </thead>
          <tbody>
            @for (doc of p.data; track doc.id) {
              <tr>
                <td>{{ doc.title }}</td>
                <td><app-status-badge [status]="doc.status" /></td>
                <td>{{ doc.signers_signed }}/{{ doc.signers_total }}</td>
                <td>{{ doc.created_at | date: 'short' }}</td>
                <td><a [routerLink]="['/documentos', doc.id]">Ver</a></td>
              </tr>
            }
          </tbody>
        </table>

        @if (p.meta.last_page > 1) {
          <nav class="pager">
            <button type="button" (click)="goTo(p.meta.current_page - 1)" [disabled]="p.meta.current_page <= 1">Anterior</button>
            <span>Página {{ p.meta.current_page }} de {{ p.meta.last_page }}</span>
            <button type="button" (click)="goTo(p.meta.current_page + 1)" [disabled]="p.meta.current_page >= p.meta.last_page">Siguiente</button>
          </nav>
        }
      }
    }
  `,
})
export class DocumentList implements OnInit {
  private readonly documents = inject(DocumentsService);

  readonly statusOptions = (Object.keys(DOCUMENT_STATUS_LABELS) as DocumentStatus[]).map((value) => ({
    value,
    label: DOCUMENT_STATUS_LABELS[value],
  }));

  readonly page = signal<Paginated<SignDocument> | null>(null);
  readonly status = signal<DocumentStatus | ''>('');
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);
  private currentPage = 1;

  ngOnInit(): void {
    this.load();
  }

  filter(status: DocumentStatus | ''): void {
    this.status.set(status);
    this.currentPage = 1;
    this.load();
  }

  goTo(page: number): void {
    this.currentPage = page;
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.error.set(null);

    this.documents.list(this.currentPage, this.status()).subscribe({
      next: (page) => {
        this.page.set(page);
        this.loading.set(false);
      },
      error: (e: unknown) => {
        this.error.set(errorMessage(e));
        this.loading.set(false);
      },
    });
  }
}
