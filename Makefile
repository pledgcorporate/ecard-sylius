DC_EXEC_TEST_PHP=docker-compose exec -e APP_ENV=test php
BIN_PATH=tests/Application/vendor/bin

.PHONY: help
help: ## This help
	@grep -Eh '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

up: ## Up containers
	docker-compose up

up-d:
	docker-compose up -d

ps: ## List containers
	docker-compose ps

stop: ## Stops running containers
	docker-compose stop

composer-validate:
	./bin/composer validate --ansi --strict

doctrine-validate:
	./bin/console doctrine:schema:validate

phpstan: ## launch phpstan
	$(DC_EXEC_TEST_PHP) $(BIN_PATH)/phpstan analyse -c phpstan.neon -l max src/

phpunit: ## launch phpunit tests
	$(DC_EXEC_TEST_PHP) $(BIN_PATH)/phpunit --testdox

ecs-check: ## Check coding styles
	$(DC_EXEC_TEST_PHP) $(BIN_PATH)/ecs check src tests/Unit

ecs-fix: ## fix coding styles
	$(DC_EXEC_TEST_PHP) $(BIN_PATH)/ecs check src tests/Unit --fix

install: ## install Sylius
	bin/console sylius:install # use EUR currency and fr_FR locale when ask

db:
	docker-compose exec mysql mysql -u sylius -pnopassword sylius

#behat: ## Launch behat tests
#	$(DC_EXEC_TEST_PHP) $(BIN_PATH)/behat --strict --tags="~@javascript"

ci: composer-validate doctrine-validate ecs-check phpstan phpunit #behat


