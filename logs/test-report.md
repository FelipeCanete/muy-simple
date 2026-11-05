# Reporte de pruebas

- **Fecha:** 2026-11-05 16:55:15 -03
- **Commit:** `8826d05`
- **Resultado:** ✅ Todas las pruebas pasaron

| Suite | Resultado | Resumen |
|---|---|---|
| Backend · Pest | ✅ OK | Tests:    103 passed (699 assertions) |
| Frontend · Vitest | ✅ OK | Tests  44 passed (44) |

**Entorno:** PHP 8.5.12 · Laravel Framework 13.40.0 · Pest Testing Framework 5.3.5 · Node v24.22.2 · Angular 22.2.4 · Vitest 5.1.0

Generado con `make test-log`. Reportes JUnit: `logs/junit-api.xml`, `logs/junit-web.xml`.

## Backend (Pest) — salida completa

```text

   PASS  Tests\Unit\DocumentStatusTest
  ✓ it deriva el estado según cuántos firmantes firmaron with dataset "… 0.07s  
  ✓ it deriva el estado según cuántos firmantes firmaron with dataset "uno de tres"
  ✓ it deriva el estado según cuántos firmantes firmaron with dataset "dos de tres"
  ✓ it deriva el estado según cuántos firmantes firmaron with dataset "todos"
  ✓ it deriva el estado según cuántos firmantes firmaron with dataset "único firmante pendiente"
  ✓ it deriva el estado según cuántos firmantes firmaron with dataset "único firmante firmó"
  ✓ it rechaza conteos imposibles with dataset "sin firmantes"           0.01s  
  ✓ it rechaza conteos imposibles with dataset "más firmas que firmantes"
  ✓ it rechaza conteos imposibles with dataset "firmas negativas"
  ✓ it usa los valores y etiquetas en español

   PASS  Tests\Unit\RutTest
  ✓ Rut::isValid → it acepta RUTs válidos en distintos formatos with da… 0.05s  
  ✓ Rut::isValid → it acepta RUTs válidos en distintos formatos with dataset "sin puntos"
  ✓ Rut::isValid → it acepta RUTs válidos en distintos formatos with dataset "sin guion"
  ✓ Rut::isValid → it acepta RUTs válidos en distintos formatos with dataset "DV K mayúscula"
  ✓ Rut::isValid → it acepta RUTs válidos en distintos formatos with dataset "DV k minúscula"
  ✓ Rut::isValid → it acepta RUTs válidos en distintos formatos with dataset "DV 0"
  ✓ Rut::isValid → it acepta RUTs válidos en distintos formatos with dataset "cuerpo de 7 dígitos"
  ✓ Rut::isValid → it acepta RUTs válidos en distintos formatos with dataset "con espacios alrededor"
  ✓ Rut::isValid → it rechaza RUTs inválidos with dataset "DV incorrect… 0.01s  
  ✓ Rut::isValid → it rechaza RUTs inválidos with dataset "vacío"
  ✓ Rut::isValid → it rechaza RUTs inválidos with dataset "solo DV"
  ✓ Rut::isValid → it rechaza RUTs inválidos with dataset "letras en el cuerpo"
  ✓ Rut::isValid → it rechaza RUTs inválidos with dataset "K en el cuerpo"
  ✓ Rut::isValid → it rechaza RUTs inválidos with dataset "cuerpo demasiado corto"
  ✓ Rut::isValid → it rechaza RUTs inválidos with dataset "cuerpo demasiado largo"
  ✓ Rut::isValid → it rechaza RUTs inválidos with dataset "caracteres extraños"
  ✓ Rut::isValid → it rechaza RUTs inválidos with dataset "inyección"
  ✓ Rut::normalize → it quita puntos, agrega guion y pasa la K a mayúscula with ('12.345.678-5', '12345678-5')
  ✓ Rut::normalize → it quita puntos, agrega guion y pasa la K a mayúscula with ('123456785', '12345678-5')
  ✓ Rut::normalize → it quita puntos, agrega guion y pasa la K a mayúscula with ('10000013-k', '10000013-K')
  ✓ Rut::checkDigit → it calcula el dígito verificador con módulo 11 with ('12345678', '5')
  ✓ Rut::checkDigit → it calcula el dígito verificador con módulo 11 with ('10000013', 'K')
  ✓ Rut::checkDigit → it calcula el dígito verificador con módulo 11 with ('10000004', '0')
  ✓ Rut::checkDigit → it calcula el dígito verificador con módulo 11 with ('11111111', '1')
  ✓ it enmascara el RUT para los logs

   PASS  Tests\Feature\AuthTest
  ✓ it inicia sesión con credenciales correctas                          1.69s  
  ✓ it rechaza credenciales incorrectas con un mensaje genérico          0.30s  
  ✓ it no revela si el email existe                                      0.22s  
  ✓ it valida el formato de la entrada                                   0.03s  
  ✓ it limita los intentos de login (fuerza bruta)                       1.06s  
  ✓ it expone el usuario actual solo con sesión                          0.03s  
  ✓ it cierra la sesión                                                  0.03s  

   PASS  Tests\Feature\CreateDocumentTest
  ✓ it crea el documento en estado pendiente con un enlace por firmante  0.30s  
  ✓ it genera tokens distintos para cada firmante                        0.06s  
  ✓ it exige autenticación                                               0.02s  
  ✓ it rechaza datos inválidos with dataset "sin título"                 0.03s  
  ✓ it rechaza datos inválidos with dataset "sin archivo"                0.03s  
  ✓ it rechaza datos inválidos with dataset "sin firmantes"              0.02s  
  ✓ it rechaza datos inválidos with dataset "email inválido"             0.03s  
  ✓ it rechaza datos inválidos with dataset "RUT con DV incorrecto"      0.04s  
  ✓ it rechaza datos inválidos with dataset "firmante sin RUT"           0.02s  
  ✓ it rechaza datos inválidos with dataset "campos extra en firmante"   0.02s  
  ✓ it rechaza un archivo de texto disfrazado de PDF                     0.11s  
  ✓ it rechaza un PDF que supera el tamaño máximo                        0.02s  
  ✓ it detecta firmantes duplicados aunque estén escritos distinto with… 0.02s  
  ✓ it detecta firmantes duplicados aunque estén escritos distinto with… 0.02s  
  ✓ it limita la cantidad de firmantes                                   0.03s  
  ✓ it guarda solo el nombre base del archivo original                   0.03s  

   PASS  Tests\Feature\ExampleTest
  ✓ it responde el health check                                          0.09s  

   PASS  Tests\Feature\ListDocumentsTest
  ✓ it lista los documentos con su estado y avance de firmas             0.07s  
  ✓ it solo muestra los documentos del usuario autenticado               0.02s  
  ✓ it filtra por estado                                                 0.03s  
  ✓ it rechaza un filtro de estado desconocido                           0.02s  
  ✓ it pagina los resultados                                             0.03s  
  ✓ it nunca expone tokens, rutas internas ni IPs                        0.04s  
  ✓ it muestra el detalle con los firmantes                              0.11s  
  ✓ it responde 404 al pedir el documento de otro usuario (sin revelar…  0.05s  
  ✓ it descarga el PDF propio                                            0.03s  
  ✓ it exige autenticación para listar                                   0.02s  

   PASS  Tests\Feature\SecurityAndErrorsTest
  ✓ it agrega headers de seguridad a las respuestas de la API            0.03s  
  ✓ it no habilita CORS para orígenes externos                           0.02s  
  ✓ it responde 404 en formato problem+json para rutas inexistentes      0.01s  
  ✓ it responde 405 en formato problem+json para métodos no soportados   0.02s  
  ✓ it oculta los detalles internos de errores inesperados               0.02s  
  ✓ it el seeder crea el usuario demo de forma idempotente               0.03s  

   PASS  Tests\Feature\SigningTest
  ✓ ver el enlace → it muestra el documento y el estado del firmante     0.03s  
  ✓ ver el enlace → it responde 404 con un token que no existe           0.02s  
  ✓ ver el enlace → it responde 404 con un token mal formado sin consul… 0.02s  
  ✓ ver el enlace → it responde 410 si el enlace expiró                  0.02s  
  ✓ ver el enlace → it sigue mostrando la firma realizada aunque el enl… 0.02s  
  ✓ ver el enlace → it entrega el PDF al firmante                        0.02s  
  ✓ firmar → it registra la firma y recalcula el estado del documento    0.02s  
  ✓ firmar → it marca el documento como completo cuando firma el último  0.03s  
  ✓ firmar → it no permite firmar dos veces con el mismo enlace          0.03s  
  ✓ firmar → it no permite firmar con un enlace expirado                 0.02s  
  ✓ firmar → it responde 404 con un token desconocido                    0.02s  
  ✓ firmar → it limita los intentos por IP (fuerza bruta sobre tokens)   0.09s  

   PASS  Tests\Feature\SimulateWebhookCommandTest
  ✓ it envía al webhook un payload firmado correctamente                 0.22s  
  ✓ it termina con error si la API rechaza el webhook                    0.03s  

   PASS  Tests\Feature\WebhookTest
  ✓ it marca al firmante como firmado y recalcula el estado              0.04s  
  ✓ it es idempotente: el mismo event_id no se procesa dos veces         0.05s  
  ✓ it responde OK sin cambios si el firmante ya había firmado por enla… 0.02s  
  ✓ it acepta la firma aunque el enlace del firmante haya expirado       0.03s  
  ✓ it responde 404 si el firmante no existe y no registra el evento     0.02s  
  ✓ it valida el payload with dataset "sin event_id"                     0.02s  
  ✓ it valida el payload with dataset "event_id con caracteres raros"    0.02s  
  ✓ it valida el payload with dataset "signer_id no uuid"                0.02s  
  ✓ autenticidad (HMAC) → it rechaza una firma inválida                  0.02s  
  ✓ autenticidad (HMAC) → it rechaza un payload firmado con otro secret… 0.03s  
  ✓ autenticidad (HMAC) → it rechaza requests sin headers de firma       0.02s  
  ✓ autenticidad (HMAC) → it rechaza timestamps fuera de la ventana (re… 0.02s  
  ✓ autenticidad (HMAC) → it rechaza timestamps fuera de la ventana (re… 0.02s  
  ✓ autenticidad (HMAC) → it rechaza todo si el secreto no está configu… 0.03s  

  Tests:    103 passed (699 assertions)
  Duration: 7.06s

```

## Frontend (Vitest) — salida completa

```text
Automatically searching for and using Vitest configuration file.
Application bundle generation complete. [41.288 seconds] - 2026-11-05T19:53:33.417Z


 RUN  v5.1.0 /web

 ✓ |web| src/app/shared/rut.spec.ts > RUT > acepta 12.345.678-5 78ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > acepta 12345678-5 7ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > acepta 123456785 2ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > acepta 10.000.013-K 4ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > acepta 10000013-k 10ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > acepta 10000004-0 5ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > acepta 7.654.321-6 3ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > rechaza "12345678-9" 4ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > rechaza "" 5ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > rechaza "5" 4ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > rechaza "12A45678-5" 3ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > rechaza "123456-0" 5ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > rechaza "123456789-1" 4ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > rechaza "12345678/5" 3ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > normaliza el formato 3ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > calcula el dígito verificador, incluidos K y 0 3ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > rutValidator > no marca error en un campo vacío (eso lo hace required) 13ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > rutValidator > marca error con un RUT inválido 5ms
 ✓ |web| src/app/shared/rut.spec.ts > RUT > rutValidator > acepta un RUT válido 4ms
 ✓ |web| src/app/core/auth.guard.spec.ts > guards > authGuard deja pasar con sesión 2233ms
 ✓ |web| src/app/core/auth.guard.spec.ts > guards > authGuard manda al login sin sesión 94ms
 ✓ |web| src/app/core/auth.guard.spec.ts > guards > guestGuard saca del login a quien ya tiene sesión 28ms
 ✓ |web| src/app/core/http-error.interceptor.spec.ts > httpErrorInterceptor > convierte una respuesta problem+json en ApiError 265ms
 ✓ |web| src/app/core/http-error.interceptor.spec.ts > httpErrorInterceptor > expone los errores de validación por campo 36ms
 ✓ |web| src/app/core/http-error.interceptor.spec.ts > httpErrorInterceptor > usa un mensaje genérico si la respuesta no es problem+json 15ms
 ✓ |web| src/app/core/http-error.interceptor.spec.ts > httpErrorInterceptor > informa cuando no hay conexión 41ms
 ✓ |web| src/app/core/http-error.interceptor.spec.ts > httpErrorInterceptor > con 401 en el panel, limpia la sesión y redirige al login 37ms
 ✓ |web| src/app/core/http-error.interceptor.spec.ts > httpErrorInterceptor > no redirige con 401 en la página pública de firma 33ms
 ✓ |web| src/app/documents/document-new.spec.ts > DocumentNew > parte con un firmante y permite agregar y quitar 455ms
 ✓ |web| src/app/documents/document-new.spec.ts > DocumentNew > no permite quedarse sin firmantes 63ms
 ✓ |web| src/app/documents/document-new.spec.ts > DocumentNew > no permite superar el máximo de firmantes 79ms
 ✓ |web| src/app/signing/sign-page.spec.ts > SignPage > muestra el documento y el botón de firma deshabilitado hasta aceptar 481ms
 ✓ |web| src/app/signing/sign-page.spec.ts > SignPage > con HTTP 404 (invalid_token) muestra "Enlace no válido" 41ms
 ✓ |web| src/app/documents/document-new.spec.ts > DocumentNew > valida email y RUT de cada firmante 72ms
 ✓ |web| src/app/documents/document-new.spec.ts > DocumentNew > valida el archivo antes de enviarlo 62ms
 ✓ |web| src/app/signing/sign-page.spec.ts > SignPage > con HTTP 410 (token_expired) muestra "Enlace expirado" 41ms
 ✓ |web| src/app/signing/sign-page.spec.ts > SignPage > con HTTP 429 (too_many_requests) muestra "Demasiados intentos" 30ms
 ✓ |web| src/app/signing/sign-page.spec.ts > SignPage > indica cuando el firmante ya firmó 33ms
 ✓ |web| src/app/documents/document-new.spec.ts > DocumentNew > no envía nada si el formulario es inválido 41ms
 ✓ |web| src/app/signing/sign-page.spec.ts > SignPage > firma (pidiendo antes la cookie CSRF) y muestra la confirmación 85ms
 ✓ |web| src/app/signing/sign-page.spec.ts > SignPage > si otro canal ya registró la firma (409), recarga el estado real 40ms
 ✓ |web| src/app/signing/sign-page.spec.ts > SignPage > muestra un error recuperable si la firma falla por otro motivo 42ms
 ✓ |web| src/app/documents/document-new.spec.ts > DocumentNew > envía el formulario como multipart y muestra los enlaces generados 128ms
 ✓ |web| src/app/documents/document-new.spec.ts > DocumentNew > muestra los errores de validación que devuelve la API 79ms

 Test Files  5 passed (5)
      Tests  44 passed (44)
   Start at  19:53:34
   Duration  71.90s (transform 52%, environment 27%, setup 14%, import 6%, tests 1%)

JUNIT report written to /web/test-logs/junit-web.xml
npm notice
npm notice New major version of npm available! 11.19.1 -> 12.2.2
npm notice Changelog: https://github.com/npm/cli/releases/tag/v12.2.2
npm notice To update run: npm install -g npm@12.2.2
npm notice
```
