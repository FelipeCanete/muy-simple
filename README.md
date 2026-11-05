# Mini flujo de firma de documentos

Subes un PDF, le asignas firmantes (email + RUT) y cada uno recibe un enlace único para firmar. El documento pasa de **pendiente** a **firmado parcial** y termina en **completo** cuando firma el último. Un webhook permite que un proveedor externo marque a un firmante como firmado.

- **API:** Laravel 13 (PHP 8.5) sobre FrankenPHP
- **Front:** Angular 22
- **Base de datos:** SQLite
- **Todo corre en Docker.** No necesitas PHP ni Node instalados.

---

## Cómo correrlo

Necesitas Docker (con Compose) y `make`.

```bash
make setup   # crea .env con secretos aleatorios y te muestra la clave del panel
make up      # construye y levanta todo
```

Abre **http://localhost:8090** e ingresa con las credenciales que imprimió `make setup` (también están en `.env`).

> Si el puerto 8090 está ocupado, cambia `WEB_PORT` y `APP_URL` en `.env` antes de `make up`.

### Probar el flujo completo

1. En el panel, crea un documento con dos firmantes. Por ejemplo, `12.345.678-5` y `11.111.111-1`.
2. Copia los enlaces que aparecen. **Se muestran una sola vez** (más abajo explico por qué).
3. Abre el primer enlace en otra pestaña o en modo incógnito y firma. El documento queda en **firmado parcial**.
4. Simula que el proveedor externo avisa que firmó el segundo. Su ID aparece en el detalle del documento:
   ```bash
   make webhook-demo SIGNER=<id-del-firmante>
   ```
   El documento queda en **completo**.

### Otros comandos

| Comando | Qué hace |
|---|---|
| `make test` | Corre los tests del backend y del front (en Docker) |
| `make test-log` | Corre los tests y guarda el reporte en `logs/test-report.md` |
| `make audit` | Revisa vulnerabilidades conocidas en las dependencias |
| `make logs` | Muestra los logs de la API y de nginx |
| `make down` / `make clean` | Detiene todo / detiene y **borra los datos** |

---

## La API en 30 segundos

Todas las rutas cuelgan de `/api`. Los errores **siempre** vienen con el mismo formato ([RFC 9457](https://www.rfc-editor.org/rfc/rfc9457), `application/problem+json`):

```json
{ "status": 410, "code": "token_expired", "title": "Enlace de firma expirado", "detail": "El enlace de firma expiró…" }
```

El campo `code` es estable y el front lo usa para decidir qué mostrar.

| Método | Ruta | Quién | Notas |
|---|---|---|---|
| `POST` | `/login`, `/logout` · `GET /me` | panel | Sesión por cookie (Sanctum) |
| `GET` | `/documents?status=&page=` | panel | Lista paginada con estado y avance `firmados/total` |
| `POST` | `/documents` | panel | multipart: `title`, `file` (PDF ≤ 10 MB), `signers[i][email]`, `signers[i][rut]`. Devuelve los enlaces |
| `GET` | `/documents/{id}` · `/documents/{id}/file` | panel | Detalle con firmantes / el PDF |
| `GET` | `/sign/{token}` · `/sign/{token}/file` | firmante | Lo que ve quien abre el enlace |
| `POST` | `/sign/{token}` | firmante | Firma. `409` si ya firmó, `410` si el enlace expiró |
| `POST` | `/webhooks/signatures` | proveedor | `{event_id, signer_id}` firmado con HMAC (ver abajo) |

**Webhook.** El proveedor envía:

```
X-Timestamp: 1762370000
X-Signature: sha256=<hex( HMAC_SHA256(WEBHOOK_SECRET, "{timestamp}.{body}") )>
```

Se rechaza (`401`) si la firma no calza o si el timestamp tiene más de 5 minutos de diferencia. `make webhook-demo` es un ejemplo funcional de cliente.

---

## Decisiones que tomé (y por qué)

**SQLite y no IndexedDB.** El enunciado ofrecía las dos opciones. IndexedDB vive en el navegador de cada persona, y aquí el estado tiene que ser compartido: el firmante abre su enlace en otro computador y el dueño del documento debe ver el cambio. Eso obliga a que la fuente de verdad esté en el servidor. SQLite es lo más simple que cumple eso, sin otro contenedor que mantener.

**El estado del documento se calcula, no se edita.** Hay una sola función (`DocumentStatus::fromCounts`) que dice "0 firmas → pendiente, algunas → firmado parcial, todas → completo". Tanto la firma por enlace como el webhook pasan por el mismo servicio (`SigningService`), que recalcula el estado dentro de una transacción. Así no hay dos caminos que puedan dejar el documento en un estado inconsistente.

**Carreras entre enlace y webhook.** Si un firmante firma por el enlace justo cuando llega el webhook, las dos operaciones no pueden pisarse. SQLite está configurado con transacciones `IMMEDIATE`: el lock de escritura se toma al empezar, así que las dos operaciones se ejecutan una después de la otra. El código además usa `lockForUpdate`, de modo que seguiría siendo correcto si mañana se cambia a Postgres.

**Los tokens se guardan hasheados.** Cada enlace lleva un token aleatorio de 256 bits. En la base solo queda su SHA-256. Si alguien obtiene una copia de la base, no puede firmar por nadie. El costo es que los enlaces solo se pueden mostrar al crear el documento. En un sistema real se enviarían por email (aquí el envío de correo quedó fuera de alcance) y existiría un botón de "reenviar", que generaría un token nuevo.

**El webhook es idempotente.** Los proveedores reintentan, así que recibir el mismo `event_id` dos veces devuelve `200` sin cambiar nada. Si el firmante ya había firmado por el enlace, el webhook también responde `200` (`already_signed: true`), porque para el proveedor eso es un éxito y no un error. El webhook ignora la expiración del enlace, porque esa regla aplica al enlace y no a la firma que el proveedor ya verificó.

**¿Qué pasa con "ya firmó" + "expiró"?** Primero se revisa si ya firmó. Para quien abre un enlace viejo es más útil leer "ya firmaste el 3 de noviembre" que "tu enlace expiró".

**Autenticación del panel.** El enunciado no la pedía, pero sin ella cualquiera podría listar los documentos y sus RUTs (OWASP A01). Usé Sanctum en modo SPA, con una cookie de sesión `HttpOnly` y protección CSRF. El navegador nunca maneja un token, así que no hay nada que un XSS pueda robar de `localStorage`. Los firmantes no tienen cuenta: su enlace es su credencial.

**Front y API en el mismo origen.** nginx sirve el Angular y hace de proxy de `/api` hacia la API, que no expone ningún puerto propio. Así no hace falta abrir CORS, y las cookies pueden ser `SameSite=Strict`.

**Errores con un solo formato.** Todo error (validación, negocio, 404, 429, 500) sale como problem+json con un `code` estable. Los errores inesperados nunca muestran el stack trace ni mensajes internos. En el front, un interceptor convierte todas las respuestas en un único tipo `ApiError`, así que cada pantalla decide su mensaje según el `code`.

**Qué dejé fuera a propósito.** Envío de emails, reenvío o revocación de enlaces, firma criptográfica real (PAdES / firma electrónica avanzada), roles, multi-tenant y una cola para procesar webhooks. Todo eso es razonable en producción, pero aquí habría sumado código sin mostrar más criterio.

### OWASP Top 10: dónde está cada cosa

| Riesgo | Qué se hizo |
|---|---|
| A01 Control de acceso | Panel con sesión obligatoria. Cada usuario ve solo sus documentos y el de otro usuario responde `404`, sin revelar que existe. El token da acceso a un solo firmante. |
| A02 Criptografía | Tokens aleatorios de 256 bits guardados como SHA-256. HMAC-SHA256 en el webhook, comparado con `hash_equals`. Secretos solo en `.env`, generados por `make setup`. |
| A03 Inyección | Eloquent con bindings. Validación estricta (RUT con dígito verificador, UUID, regex en `event_id`). El token se valida con `[a-f0-9]{64}` en la ruta. Angular escapa todo por defecto. |
| A04 Diseño inseguro | Enlaces que expiran (7 días) y son de un solo uso. Webhook idempotente con protección anti-replay. Transacciones con lock. |
| A05 Configuración | Headers de seguridad en la API y en nginx (CSP, `X-Frame-Options`, `nosniff`, `Referrer-Policy`). CORS cerrado. `APP_DEBUG=false`. Contenedores sin root, con `cap_drop: ALL` y `no-new-privileges`. Sin header `Server`/`X-Powered-By`. |
| A06 Componentes | Versiones estables actuales con lockfiles. `make audit` (`composer audit` y `npm audit`) sin hallazgos. |
| A07 Autenticación | Rate limit en login (5/min por email+IP), en enlaces (30/min por IP) y en webhook. Regeneración de sesión al ingresar. Mensaje genérico de "credenciales inválidas". |
| A08 Integridad | Webhook firmado con timestamp. Se guarda el SHA-256 del PDF y se muestra al firmante. El PDF se valida por contenido (magic bytes), no por extensión. |
| A09 Logging | Se registran creación, firma, logins fallidos y webhooks rechazados, **sin** tokens y con el RUT enmascarado (`****5678-5`). |
| A10 SSRF | La API no hace requests a URLs que vengan del usuario. |

Un detalle que costó encontrar: la idea inicial era `Referrer-Policy: no-referrer` también en el front, para que el token de la URL nunca viajara como Referer. El problema es que con esa política el navegador envía `Origin: null` en los POST, y Sanctum deja de reconocer al SPA. El front quedó con `same-origin`, que tampoco envía el token a sitios externos. La API mantiene `no-referrer`.

---

## Tests

```bash
make test       # o make test-log para dejar el reporte en logs/
```

- **Backend (Pest), 103 tests.** Unitarios del RUT y de la regla de estados. De integración (HTTP real contra la app, con SQLite en memoria) para crear, listar, firmar, el webhook, la autenticación y los headers y errores. Cubren los casos felices y también los bordes: PDF falso, RUT repetido escrito distinto, doble firma, token expirado, webhook con firma inválida o repetido, documentos de otro usuario, rate limiting y errores 500 sin filtrar detalles.
- **Frontend (Vitest), 44 tests.** Validador de RUT (con los mismos casos que el backend), interceptor de errores, guards, formulario de alta (firmantes dinámicos, validación, errores 422) y pantalla de firma (cada estado de error, firma exitosa, conflicto 409).

El último reporte queda en [`logs/test-report.md`](logs/test-report.md).

---

## Estructura

```
api/                 Laravel
  app/Services/SigningService.php    ← toda la lógica del flujo
  app/Enums/DocumentStatus.php       ← la regla de estados
  app/Support/Rut.php                ← validación de RUT
  app/Http/Middleware/               ← firma HMAC del webhook, headers de seguridad
  bootstrap/app.php                  ← formato único de errores
  tests/                             ← Unit + Feature (Pest)
web/                 Angular
  src/app/core/      ← auth, interceptor de errores, modelos
  src/app/documents/ ← listado, detalle, alta
  src/app/signing/   ← pantalla pública de firma
docker/              Dockerfiles, Caddyfile, nginx.conf
scripts/test-log.sh  reporte de tests
```
