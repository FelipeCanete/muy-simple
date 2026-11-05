import { HttpErrorResponse } from '@angular/common/http';

/**
 * Error normalizado de la API. El backend responde siempre con
 * application/problem+json ({ status, code, title, detail, errors? }).
 */
export class ApiError extends Error {
  constructor(
    readonly status: number,
    readonly code: string,
    readonly title: string,
    readonly detail: string,
    readonly fieldErrors: Record<string, string[]> = {},
  ) {
    super(detail);
    this.name = 'ApiError';
  }

  /** Lista plana de mensajes de validación, útil para mostrarlos juntos. */
  get messages(): string[] {
    return Object.values(this.fieldErrors).flat();
  }

  static fromHttpError(error: HttpErrorResponse): ApiError {
    if (error.status === 0) {
      return new ApiError(
        0,
        'network_error',
        'Sin conexión',
        'No se pudo conectar con el servidor. Revisa tu conexión e inténtalo de nuevo.',
      );
    }

    const body = isProblem(error.error) ? error.error : null;

    return new ApiError(
      error.status,
      body?.code ?? 'http_error',
      body?.title ?? 'Error',
      body?.detail ?? 'Ocurrió un error inesperado. Inténtalo más tarde.',
      body?.errors ?? {},
    );
  }
}

interface ProblemBody {
  code?: string;
  title?: string;
  detail?: string;
  errors?: Record<string, string[]>;
}

function isProblem(value: unknown): value is ProblemBody {
  return typeof value === 'object' && value !== null && ('code' in value || 'detail' in value);
}

/** Convierte cualquier error en un mensaje apto para el usuario. */
export function errorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.detail;
  }
  return 'Ocurrió un error inesperado. Inténtalo más tarde.';
}
