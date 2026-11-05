SHELL := /bin/sh
COMPOSE := docker compose
COMPOSE_TEST := docker compose --profile test

.PHONY: help setup up down logs ps test test-api test-web test-log audit webhook-demo clean

help: ## Muestra los comandos disponibles
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

setup: .env ## Genera .env con secretos aleatorios (solo la primera vez)

.env:
	@cp .env.example .env
	@sed -i.bak "s|^APP_KEY=.*|APP_KEY=base64:$$(openssl rand -base64 32)|" .env
	@sed -i.bak "s|^WEBHOOK_SECRET=.*|WEBHOOK_SECRET=$$(openssl rand -hex 32)|" .env
	@sed -i.bak "s|^DEMO_USER_PASSWORD=.*|DEMO_USER_PASSWORD=$$(openssl rand -hex 8)|" .env
	@rm -f .env.bak
	@echo ".env creado. Credenciales del panel:"
	@grep -E '^DEMO_USER_(EMAIL|PASSWORD)=' .env

up: .env ## Construye y levanta la app en http://localhost:8090
	$(COMPOSE) up -d --build
	@echo "App en http://localhost:$${WEB_PORT:-8090}"
	@grep -E '^DEMO_USER_(EMAIL|PASSWORD)=' .env

down: ## Detiene los contenedores (conserva los datos)
	$(COMPOSE) down

logs: ## Sigue los logs de la API y el front
	$(COMPOSE) logs -f api web

ps: ## Estado de los contenedores
	$(COMPOSE) ps

test: test-api test-web ## Corre todos los tests

test-api: ## Tests del backend (Pest)
	$(COMPOSE_TEST) run --rm --build api-test

test-web: ## Tests del frontend (Vitest)
	$(COMPOSE_TEST) run --rm --build web-test

test-log: ## Corre los tests y guarda el reporte en logs/
	@./scripts/test-log.sh

audit: ## Busca vulnerabilidades conocidas en dependencias
	$(COMPOSE_TEST) run --rm --build api-test composer audit --locked
	$(COMPOSE_TEST) run --rm --build web-test npm audit --omit=dev

webhook-demo: ## Simula el callback del proveedor: make webhook-demo SIGNER=<uuid>
	@test -n "$(SIGNER)" || (echo "Uso: make webhook-demo SIGNER=<id del firmante>"; exit 1)
	$(COMPOSE) exec api php artisan webhook:simulate $(SIGNER)

clean: ## Borra contenedores y volúmenes (¡elimina los datos!)
	$(COMPOSE) down -v
