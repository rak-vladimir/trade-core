COMPOSE = docker compose \
	--env-file .env \
	-f _docker/docker-compose.yml

LOCAL_UID := $(shell id -u)
LOCAL_GID := $(shell id -g)

export LOCAL_UID
export LOCAL_GID

.DEFAULT_GOAL := help

.PHONY: init help config build up down restart ps logs \
	shell bash artisan composer composer-install \
	npm npm-install key migrate seed fresh test

help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| sed -E 's/:.*## / - /'

init:
	$(COMPOSE) up -d
	$(MAKE) artisan ARGS="app:init"
	$(MAKE) npm ARGS="run build"

config: ## Validate Docker Compose configuration
	$(COMPOSE) config

build: ## Build Docker images
	$(COMPOSE) build

up: ## Build and start containers
	$(COMPOSE) up -d --build

down: ## Stop containers
	$(COMPOSE) down

restart: ## Restart containers
	$(COMPOSE) restart

ps: ## Show container status
	$(COMPOSE) ps

logs: ## Follow all container logs
	$(COMPOSE) logs -f --tail=100

shell: ## Enter application container as app
	$(COMPOSE) exec --user app app sh

bash: ## Enter application container as app
	$(COMPOSE) exec --user app app bash

artisan: ## Run Artisan: make artisan ARGS="list"
	$(COMPOSE) exec --user app app php artisan $(ARGS)

composer: ## Run Composer: make composer ARGS="require ..."
	$(COMPOSE) exec --user app app composer $(ARGS)

composer-install: ## Install Composer dependencies
	$(COMPOSE) exec --user app app composer install --prefer-dist --no-interaction

npm: ## Run npm: make npm ARGS="install package"
	$(COMPOSE) run --rm --no-deps node npm $(ARGS)

npm-install: ## Install npm dependencies
	$(COMPOSE) run --rm --no-deps node npm install

npm-ci: ## Clean install from package-lock.json
	$(COMPOSE) run --rm --no-deps node npm ci

key: ## Generate Laravel application key
	$(COMPOSE) exec --user app app php artisan key:generate

migrate: ## Run database migrations
	$(COMPOSE) exec --user app app php artisan migrate

seed: ## Run database seeders
	$(COMPOSE) exec --user app app php artisan db:seed

fresh: ## Recreate database structure and run seeders
	$(COMPOSE) exec --user app app php artisan migrate:fresh --seed

test: ## Run Laravel tests
	$(COMPOSE) exec --user app app sh -lc 'php artisan config:clear && DB_DATABASE=trade_core_test APP_ENV=testing php artisan test'
