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
	$(DC_EXEC_TEST_PHP) composer validate --ansi --strict

doctrine-validate:
	$(DC_EXEC_TEST_PHP) tests/Application/bin/console doctrine:schema:validate

phpstan: ## launch phpstan
	$(DC_EXEC_TEST_PHP) $(BIN_PATH)/phpstan analyse -c phpstan.neon -l max src/

psalm:
	$(DC_EXEC_TEST_PHP) $(BIN_PATH)/psalm

phpspec: ## Launch phpspec tests
	$(DC_EXEC_TEST_PHP) $(BIN_PATH)/phpspec run

phpunit: ## launch phpunit tests
	$(DC_EXEC_TEST_PHP) $(BIN_PATH)/phpunit

behat: ## Launch behat tests
	$(DC_EXEC_TEST_PHP) $(BIN_PATH)/behat --strict --tags="~@javascript"

ci: composer-validate doctrine-validate phpstan psalm phpspec phpunit behat


