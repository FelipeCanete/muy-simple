import { defineConfig } from 'vitest/config';

// Dentro de Docker, o en una máquina cargada, compilar el primer componente
// puede tardar más que los límites por defecto (5 s por test, 10 s por hook).
// Los subimos para que la suite no falle por lentitud del entorno.
export default defineConfig({
  test: {
    testTimeout: 30_000,
    hookTimeout: 60_000,
  },
});
