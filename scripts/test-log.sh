#!/bin/sh
# Corre las suites de backend y frontend dentro de Docker y deja:
#   logs/test-report.md   resumen legible + salida completa
#   logs/api-tests.log    salida de Pest
#   logs/web-tests.log    salida de Vitest
#   logs/junit-api.xml    reporte JUnit (para CI)
#   logs/junit-web.xml    reporte JUnit (para CI)
# Sale con código distinto de 0 si alguna suite falla.

set -u
cd "$(dirname "$0")/.."

LOGS=logs
REPORT=$LOGS/test-report.md
API_LOG=$LOGS/api-tests.log
WEB_LOG=$LOGS/web-tests.log
COMPOSE="docker compose --profile test"

mkdir -p "$LOGS"

echo "Construyendo imágenes de test…"
$COMPOSE build api-test web-test > /dev/null || { echo "Falló el build de las imágenes de test" >&2; exit 1; }

echo "Corriendo tests del backend (Pest)…"
$COMPOSE run --rm -T api-test \
  php artisan test --colors=never --log-junit test-logs/junit-api.xml > "$API_LOG" 2>&1
API_RC=$?

echo "Corriendo tests del frontend (Vitest)…"
$COMPOSE run --rm -T -e NO_COLOR=1 -e FORCE_COLOR=0 web-test \
  npx ng test --watch=false --no-progress --reporters=verbose --reporters=junit \
  --output-file=test-logs/junit-web.xml > "$WEB_LOG" 2>&1
WEB_RC=$?

# Versiones del entorno de test (para que el reporte sea reproducible).
PHP_V=$($COMPOSE run --rm -T api-test php -r 'echo PHP_VERSION;' 2>/dev/null)
LARAVEL_V=$($COMPOSE run --rm -T api-test php artisan --version 2>/dev/null | tr -d '\r')
PEST_V=$($COMPOSE run --rm -T api-test ./vendor/bin/pest --version --colors=never 2>/dev/null | tr -d '\r\n' | sed 's/^ *//; s/\. *$//')
NODE_V=$($COMPOSE run --rm -T web-test node -v 2>/dev/null)
ANGULAR_V=$($COMPOSE run --rm -T web-test node -p "require('@angular/core/package.json').version" 2>/dev/null)
VITEST_V=$($COMPOSE run --rm -T web-test node -p "require('vitest/package.json').version" 2>/dev/null)

status() { [ "$1" -eq 0 ] && echo "✅ OK" || echo "❌ FALLÓ"; }
summary_api=$(grep -E '^\s*Tests:' "$API_LOG" | tail -1 | sed 's/^ *//')
summary_web=$(grep -E '^\s*Tests ' "$WEB_LOG" | tail -1 | sed 's/^ *//')

if [ $API_RC -eq 0 ] && [ $WEB_RC -eq 0 ]; then OVERALL="✅ Todas las pruebas pasaron"; else OVERALL="❌ Hay pruebas fallidas"; fi

{
  echo "# Reporte de pruebas"
  echo
  echo "- **Fecha:** $(date '+%Y-%m-%d %H:%M:%S %Z')"
  echo "- **Commit:** \`$(git rev-parse --short HEAD 2>/dev/null || echo 'sin git')\`$(git diff --quiet 2>/dev/null || echo ' (con cambios sin commitear)')"
  echo "- **Resultado:** $OVERALL"
  echo
  echo "| Suite | Resultado | Resumen |"
  echo "|---|---|---|"
  echo "| Backend · Pest | $(status $API_RC) | ${summary_api:-sin resumen} |"
  echo "| Frontend · Vitest | $(status $WEB_RC) | ${summary_web:-sin resumen} |"
  echo
  echo "**Entorno:** PHP $PHP_V · $LARAVEL_V · $PEST_V · Node $NODE_V · Angular $ANGULAR_V · Vitest $VITEST_V"
  echo
  echo "Generado con \`make test-log\`. Reportes JUnit: \`logs/junit-api.xml\`, \`logs/junit-web.xml\`."
  echo
  echo "## Backend (Pest) — salida completa"
  echo
  echo '```text'
  cat "$API_LOG"
  echo '```'
  echo
  echo "## Frontend (Vitest) — salida completa"
  echo
  echo '```text'
  cat "$WEB_LOG"
  echo '```'
} > "$REPORT"

echo
echo "$OVERALL"
echo "  Backend:  $(status $API_RC)  ${summary_api}"
echo "  Frontend: $(status $WEB_RC)  ${summary_web}"
echo "Reporte: $REPORT"

[ $API_RC -eq 0 ] && [ $WEB_RC -eq 0 ]
