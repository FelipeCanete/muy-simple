import { FormControl } from '@angular/forms';
import { isValidRut, normalizeRut, rutCheckDigit, rutValidator } from './rut';

// Mismos casos que api/tests/Unit/RutTest.php: front y back deben coincidir.
describe('RUT', () => {
  it.each(['12.345.678-5', '12345678-5', '123456785', '10.000.013-K', '10000013-k', '10000004-0', '7.654.321-6'])(
    'acepta %s',
    (rut) => {
      expect(isValidRut(rut)).toBe(true);
    },
  );

  it.each(['12345678-9', '', '5', '12A45678-5', '123456-0', '123456789-1', '12345678/5'])('rechaza "%s"', (rut) => {
    expect(isValidRut(rut)).toBe(false);
  });

  it('normaliza el formato', () => {
    expect(normalizeRut('12.345.678-5')).toBe('12345678-5');
    expect(normalizeRut('10000013k')).toBe('10000013-K');
  });

  it('calcula el dígito verificador, incluidos K y 0', () => {
    expect(rutCheckDigit('12345678')).toBe('5');
    expect(rutCheckDigit('10000013')).toBe('K');
    expect(rutCheckDigit('10000004')).toBe('0');
  });

  describe('rutValidator', () => {
    it('no marca error en un campo vacío (eso lo hace required)', () => {
      expect(rutValidator(new FormControl(''))).toBeNull();
    });

    it('marca error con un RUT inválido', () => {
      expect(rutValidator(new FormControl('12345678-9'))).toEqual({ rut: true });
    });

    it('acepta un RUT válido', () => {
      expect(rutValidator(new FormControl('12.345.678-5'))).toBeNull();
    });
  });
});
