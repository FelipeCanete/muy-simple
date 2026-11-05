import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { User } from './models';

/**
 * Sesión del panel con Sanctum: la sesión vive en una cookie HttpOnly,
 * así que aquí solo guardamos quién es el usuario (nunca un token).
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);

  private readonly currentUser = signal<User | null>(null);
  private loaded: Promise<User | null> | null = null;

  readonly user = this.currentUser.asReadonly();
  readonly isLoggedIn = computed(() => this.currentUser() !== null);

  /** Carga el usuario actual una sola vez (lo usa el guard). */
  ensureLoaded(): Promise<User | null> {
    this.loaded ??= firstValueFrom(this.http.get<{ data: User }>('/api/me'))
      .then((res) => {
        this.currentUser.set(res.data);
        return res.data;
      })
      .catch(() => {
        this.currentUser.set(null);
        return null;
      });

    return this.loaded;
  }

  async login(email: string, password: string): Promise<User> {
    // Sanctum entrega la cookie XSRF-TOKEN; Angular la reenvía como header.
    await firstValueFrom(this.http.get('/sanctum/csrf-cookie'));
    const res = await firstValueFrom(this.http.post<{ data: User }>('/api/login', { email, password }));
    this.currentUser.set(res.data);
    this.loaded = Promise.resolve(res.data);
    return res.data;
  }

  async logout(): Promise<void> {
    try {
      await firstValueFrom(this.http.post('/api/logout', {}));
    } finally {
      this.clear();
    }
  }

  clear(): void {
    this.currentUser.set(null);
    this.loaded = Promise.resolve(null);
  }
}
