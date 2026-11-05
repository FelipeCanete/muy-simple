import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { CreatedDocumentResponse, DocumentStatus, Paginated, SignDocument } from '../core/models';

export interface NewSigner {
  email: string;
  rut: string;
}

@Injectable({ providedIn: 'root' })
export class DocumentsService {
  private readonly http = inject(HttpClient);

  list(page = 1, status: DocumentStatus | '' = ''): Observable<Paginated<SignDocument>> {
    let params = new HttpParams().set('page', page);
    if (status) {
      params = params.set('status', status);
    }
    return this.http.get<Paginated<SignDocument>>('/api/documents', { params });
  }

  get(id: string): Observable<{ data: SignDocument }> {
    return this.http.get<{ data: SignDocument }>(`/api/documents/${encodeURIComponent(id)}`);
  }

  create(title: string, file: File, signers: NewSigner[]): Observable<CreatedDocumentResponse> {
    const body = new FormData();
    body.append('title', title);
    body.append('file', file, file.name);
    signers.forEach((signer, i) => {
      body.append(`signers[${i}][email]`, signer.email);
      body.append(`signers[${i}][rut]`, signer.rut);
    });
    return this.http.post<CreatedDocumentResponse>('/api/documents', body);
  }

  fileUrl(id: string): string {
    return `/api/documents/${encodeURIComponent(id)}/file`;
  }
}
