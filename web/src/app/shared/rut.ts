import { AbstractControl, ValidationErrors, ValidatorFn } from '@angular/forms';

/**
 * RUT chileno: mismo algoritmo que api/app/Support/Rut.php.
 * Validar también en el front es solo para dar feedback inmediato;
 * la validación que manda es la del backend.
 */
export function normalizeRut(value: string): string {
  const clean = value.replace(/[^0-9kK]/g, '').toUpperCase();
  return clean.length < 2 ? clean : `${clean.slice(0, -1)}-${clean.slice(-1)}`;
}

export function rutCheckDigit(body: string): string {
  let sum = 0;
  let factor = 2;

  for (let i = body.length - 1; i >= 0; i--) {
    sum += Number(body[i]) * factor;
    factor = factor === 7 ? 2 : factor + 1;
  }

  const dv = 11 - (sum % 11);
  if (dv === 11) return '0';
  if (dv === 10) return 'K';
  return String(dv);
}

export function isValidRut(value: string): boolean {
  if (!/^[0-9.]+-?[0-9kK]$/.test(value.trim())) {
    return false;
  }

  const match = /^(\d{7,8})-([\dK])$/.exec(normalizeRut(value));
  return match !== null && rutCheckDigit(match[1]) === match[2];
}

/** Validador para Reactive Forms. Deja los vacíos a Validators.required. */
export const rutValidator: ValidatorFn = (control: AbstractControl): ValidationErrors | null => {
  const value = control.value;
  if (value === null || value === undefined || value === '') {
    return null;
  }
  return typeof value === 'string' && isValidRut(value) ? null : { rut: true };
};
