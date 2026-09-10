# Yume — one-command development.
#
#   make up      first run: writes .env, builds, starts, migrates, seeds an admin
#   make help    every target

SHELL := /bin/bash
COMPOSE := docker compose
COMPOSE_PROD := docker compose -f docker-compose.yml -f docker-compose.prod.yml
API := $(COMPOSE) exec -T app
CONSOLE := $(API) php /var/www/html/apps/api/bin/console

.DEFAULT_GOAL := help
.PHONY: help up down restart build rebuild logs shell psql migrate migrate-status \
        seed-admin routes features test test-unit test-security stan lint fix check \
        ps clean fresh prod-up prod-down prod-logs env

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

env: ## Create .env from .env.example with generated secrets (no-op if it exists)
	@if [ -f .env ]; then \
		echo "  .env already exists — leaving it alone."; \
	else \
		cp .env.example .env; \
		SECRET=$$(openssl rand -base64 32); \
		DBPASS=$$(openssl rand -base64 32 | tr -d '/+=' | head -c 32); \
		sed -i.bak "s|^APP_SECRET=.*|APP_SECRET=$$SECRET|" .env; \
		sed -i.bak "s|^DB_PASSWORD=.*|DB_PASSWORD=$$DBPASS|" .env; \
		rm -f .env.bak; \
		echo "  Wrote .env with generated secrets."; \
	fi

up: env ## Start the full development stack and migrate (the one command)
	$(COMPOSE) up -d --build
	@echo "  Waiting for the API to become ready…"
	@for i in $$(seq 1 60); do \
		curl -fsS http://localhost:$${APP_PORT:-8080}/health >/dev/null 2>&1 && break; \
		sleep 1; \
	done
	@$(MAKE) --no-print-directory migrate
	@echo ""
	@echo "  API      http://localhost:$${APP_PORT:-8080}/health"
	@echo "  Adminer  http://localhost:$${ADMINER_PORT:-8081}"
	@echo "  Mailpit  http://localhost:$${MAILPIT_UI_PORT:-8025}"
	@echo ""
	@echo "  Create the first admin:  make seed-admin"

down: ## Stop the stack, keep the data
	$(COMPOSE) down

restart: ## Restart the application containers
	$(COMPOSE) restart app worker nginx

build: ## Build images
	$(COMPOSE) build

rebuild: ## Rebuild images from scratch
	$(COMPOSE) build --no-cache

ps: ## Show container status
	$(COMPOSE) ps

logs: ## Follow logs (make logs SERVICE=app)
	$(COMPOSE) logs -f $(or $(SERVICE),)

shell: ## Shell inside the API container
	$(COMPOSE) exec app sh

psql: ## psql session against the development database
	$(COMPOSE) exec postgres psql -U $${DB_USERNAME:-yume} -d $${DB_DATABASE:-yume}

migrate: ## Apply pending migrations
	$(CONSOLE) migrate

migrate-status: ## List applied and pending migrations
	$(CONSOLE) migrate:status

seed-admin: ## Create an admin account (make seed-admin USER=… EMAIL=… PASS=…)
	$(CONSOLE) user:create \
		--username=$(or $(USER),admin) \
		--email=$(or $(EMAIL),admin@yume.local) \
		--password=$(or $(PASS),change-this-immediately-please) \
		--role=admin

routes: ## Print the route table with its access rules
	$(CONSOLE) routes

features: ## List feature flags and their rollout strategy
	$(CONSOLE) feature:list

test: ## Run the whole test suite
	$(API) vendor/bin/phpunit -c apps/api/phpunit.xml

test-unit: ## Run unit tests only
	$(API) vendor/bin/phpunit -c apps/api/phpunit.xml --testsuite=unit

test-security: ## Run the security regression suite
	$(API) vendor/bin/phpunit -c apps/api/phpunit.xml --testsuite=security

stan: ## Static analysis
	$(API) vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M

lint: ## Check code style
	$(API) vendor/bin/php-cs-fixer fix --dry-run --diff

fix: ## Apply code style fixes
	$(API) vendor/bin/php-cs-fixer fix

check: lint stan test ## Everything CI runs

fresh: ## Destroy the database and start over
	$(COMPOSE) down -v
	$(MAKE) --no-print-directory up

clean: ## Remove containers, volumes and build cache
	$(COMPOSE) down -v --remove-orphans
	docker image rm -f yume-api:latest yume-worker:latest 2>/dev/null || true

prod-up: ## Start the production stack (needs DOMAIN and ACME_EMAIL in .env)
	$(COMPOSE_PROD) up -d --build

prod-down: ## Stop the production stack
	$(COMPOSE_PROD) down

prod-logs: ## Follow production logs
	$(COMPOSE_PROD) logs -f
