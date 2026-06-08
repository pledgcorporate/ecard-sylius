# docker-sylius-skeleton Makefile
#
# First-time setup:
#   cp .env.example .env   # edit APP_NAME, APP_DOMAIN, passwords
#   make install           # build images, start containers, install Sylius
#
# Daily usage:
#   make up    / make down
#   make shell             # PHP container bash
#   make console CMD="..."  # run bin/console
#   make logs

ifneq (,$(wildcard .env))
  include .env
  export
endif

APP_NAME    	?= sylius
APP_DOMAIN  	?= sylius.docker
TRAEFIK_NETWORK ?= traefik-net
COMPOSE     	:= docker compose
PHP         	:= $(COMPOSE) exec php

.PHONY: env certs create-project install setup up down build shell console cc cc_no_warmup cc_manual logs ps help

## env: Ensure .env file exists
env:
	@if [ ! -f .env ]; then \
		echo ""; \
		echo ">>> Creating .env file..."; \
		echo ""; \
		cp .env.example .env; \
		echo "- .env created from .env.example — review it before continuing."; \
		echo ""; \
		echo ""; \
	fi

## certs: Generate local TLS certificates using mkcert
certs:
	@bash ./.docker/traefik/scripts/generate-certs.sh

## create-project: First-time setup on a fresh skeleton (create-project)
create-project: certs build up _wait-db _sylius-create-project _sylius-set-config fix_permissions

## install: First-time setup on a fresh skeleton (create-project → DB → assets)
install: cc_no_warmup _sylius-install fix_permissions cc_no_warmup display_info

## setup: Setup for subsequent developers — use this after cloning an existing project
setup: certs build up _wait-db _sylius-setup display_info

## deploy_pledg_plugin: Deploys Pledg plugin on sylius container
deploy_pledg_plugin: remove_pledg_plugin install_pledg_plugin fix_permissions cc_no_warmup

## install_pledg_plugin: Copy Pledg plugin config file(s) and require Pledg plugin composer package
install_pledg_plugin: remove_pledg_plugin
	@echo ">>> Running Pledg plugin install..."
	@echo ""
	$(COMPOSE) cp ./.docker/php/sylius_installation/config/routes/pledg_sylius_payment.yaml php:/var/www/html/config/routes/pledg_sylius_payment.yaml
	$(PHP) composer require "pledg/sylius-payment-plugin":"dev-upgrade-sylius-2.x"
	@echo ""
	@echo ""

## remove_pledg_plugin: removes Pledg plugin from sylius container
remove_pledg_plugin:
	@echo ">>> Running Pledg plugin from container..."
	@echo ""
	$(PHP) rm -rf /var/www/html/config/routes/pledg_sylius_payment.yaml
	$(PHP) composer remove "pledg/sylius-payment-plugin"
	@echo ""
	@echo ""

## up: Start all containers in detached mode
display_info:
	@echo ""
	@echo "Sylius is ready!"
	@echo "  App:    https://$(APP_DOMAIN)"
	@echo "  Admin:  https://$(APP_DOMAIN)/admin"
	@echo "  Mail:   https://mail.$(APP_DOMAIN)"
	@echo ""

## up: Start all containers in detached mode
up:
	@echo ""
	@echo ">>> Ensuring Docker network '$(TRAEFIK_NETWORK)' exists..."
	@echo ""
	@docker network inspect $(TRAEFIK_NETWORK) > /dev/null 2>&1 \
		|| docker network create $(TRAEFIK_NETWORK)
	@echo ""
	@echo ""
	@echo ">>> Starting Traefik..."
	@echo ""
	@echo "Traefik is up. Dashboard: https://traefik.docker"
	@echo ""
	@echo ""
	$(COMPOSE) up -d

## down: Stop and remove containers (volumes are preserved)
down:
	$(COMPOSE) down
	@echo ""

## build: Build Docker images
build:
	$(COMPOSE) build
	@echo ""

## shell: Open a bash shell in the PHP container
shell:
	$(PHP) bash

## console: Run a Symfony console command — usage: make console CMD="cache:clear"
console:
	$(PHP) php bin/console $(CMD)

## cc: Clear Symfony cache
cc:
	$(PHP) php bin/console cache:clear

## cc_no_warmup: Clear Symfony cache with the '--no-warmup' flag
cc_no_warmup:
	$(PHP) php bin/console cache:clear --no-warmup

## cc_manual: deletes /var/www/html/var/cache/* (quicker than "make cc")
cc_manual:
	@echo ">>> Deleting var/cache/* ..."
	@echo ""
	$(PHP) bash -c 'rm -rf /var/www/html/var/cache/*'

## fix_permissions:
fix_permissions:
	@echo ">>> Set /var/www/html ownership to www-data:www-data ..."
	@echo ""
	$(COMPOSE) exec php chown -R www-data:www-data /var/www/html

## logs: Follow logs for all services (or SERVICES="php nginx" make logs)
logs:
	$(COMPOSE) logs -f $(SERVICES)

## ps: Show running services
ps:
	$(COMPOSE) ps

## help: List available targets
help:
	@grep -E '^## ' Makefile | sed 's/^## //' | column -t -s ':'

# ─── Internal targets ────────────────────────────────────────────────────────

_wait-db:
	@echo ""
	@echo ">>> Waiting for MariaDB to be ready..."
	@echo ""
	@$(COMPOSE) exec mariadb bash -c \
		'until mariadb-admin ping -u root -p"$$MYSQL_ROOT_PASSWORD" --silent 2>/dev/null; do sleep 1; done'
	@echo "MariaDB is ready."
	@echo ""
	@echo ""

_sylius-create-project:
	@echo ""
	@echo ">>> Installing Sylius via Composer (this takes several minutes)..."
	@echo ""
# 	the env var 'SYLIUS_VERSION' is defined in docker-compose.yml
	$(PHP) bash -c 'composer create-project sylius/sylius-standard /tmp/sylius-install $${SYLIUS_VERSION} --prefer-dist'
	@echo ""
	$(PHP) bash -c 'cp -rn /tmp/sylius-install/. /var/www/html/ && rm -rf /tmp/sylius-install'
	@echo ""
	@echo ""

_sylius-set-config:
	@echo ">>> Copying Sylius config files before install..."
	@echo ""
	$(PHP) rm -rf /var/www/html/.env*
	$(COMPOSE) cp ./.env php:/var/www/html/.env
	$(PHP) rm -rf /var/www/html/config/parameters.yaml
	$(COMPOSE) cp ./.docker/php/sylius_installation/config/parameters.yaml php:/var/www/html/config/parameters.yaml
	$(PHP) rm -rf /var/www/html/config/packages/dev/pledg_fixtures.yaml
	$(PHP) rm -rf /var/www/html/config/packages/dev/fixtures
	$(COMPOSE) cp ./.docker/php/sylius_installation/config/packages/dev php:/var/www/html/config/packages
	@echo ""
	@echo ""

_sylius-install:
	@echo ">>> Running Sylius install (migrations + fixtures + assets)..."
	@echo ""
#	you can disable the fixtures installation by removing the "--fixture-suite=pledg_dev_fixtures_suite" flag
	$(PHP) php bin/console sylius:install --no-interaction --fixture-suite=pledg_dev_fixtures_suite
	@echo ">>> Running database migrations..."
	@echo ""
	$(PHP) php bin/console doctrine:migrations:migrate --no-interaction
	@echo ""
	@echo ""
	@echo ">>> Installing assets..."
	@echo ""
	$(PHP) php bin/console assets:install
	@echo ""
	@echo ""
	@echo ">>> Building frontend assets..."
	@echo ""
	$(PHP) npm install
	$(PHP) npm run build
	$(PHP) php bin/console cache:warmup
