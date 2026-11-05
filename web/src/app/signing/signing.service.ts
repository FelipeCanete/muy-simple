import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, switchMap } from 'rxjs';
import { SigningPage } from '../core/models';

@Injectable({ providedIn: 'root' })
export class SigningService {
  private readonly http = inject(HttpClient);

  get(token: string): Observable<{ data: SigningPage }> {
    return this.http.get<{ data: SigningPage }>(`/api/sign/${encodeURIComponent(token)}`);
  }

  sign(token: string): Observable<{ data: SigningPage }> {
    // El POST pasa por la protección CSRF de Sanctum: primero pedimos la cookie.
    return this.http
      .get('/sanctum/csrf-cookie')
      .pipe(switchMap(() => this.http.post<{ data: SigningPage }>(`/api/sign/${encodeURIComponent(token)}`, {})));
  }

  fileUrl(token: string): string {
    return `/api/sign/${encodeURIComponent(token)}/file`;
  }
}
