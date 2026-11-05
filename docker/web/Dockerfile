# syntax=docker/dockerfile:1
# Imagen del front (Angular). Contexto de build: raíz del repo.

FROM node:24-alpine AS deps
WORKDIR /web
COPY web/package.json web/package-lock.json ./
RUN npm ci --no-audit --no-fund

# ---------------------------------------------------------------------------
# test: corre la suite de Vitest (sin watch).
FROM deps AS test
COPY web/ .
RUN chown -R node:node /web
USER node
CMD ["npx", "ng", "test", "--watch=false"]

# ---------------------------------------------------------------------------
FROM deps AS build
COPY web/ .
RUN npx ng build --configuration production

# ---------------------------------------------------------------------------
# prod: nginx sin privilegios sirve el build y hace proxy de /api a la API.
FROM nginxinc/nginx-unprivileged:1-alpine AS prod
COPY docker/web/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /web/dist/web/browser /usr/share/nginx/html
EXPOSE 8080
