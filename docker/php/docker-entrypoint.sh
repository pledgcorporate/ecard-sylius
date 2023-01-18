#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'bin/console' ]; then
	mkdir -p tests/Application/var/cache tests/Application/var/log tests/Application/public/media
	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var tests/Application/public/media
	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var tests/Application/public/media

	composer install --prefer-dist --no-interaction
	tests/Application/bin/console assets:install --no-interaction
	tests/Application/bin/console sylius:theme:assets:install --no-interaction

	until tests/Application/bin/console doctrine:query:sql "select 1" >/dev/null 2>&1; do
	    (>&2 echo "Waiting for MySQL to be ready...")
		sleep 1
	done

    tests/Application/bin/console doctrine:migrations:migrate --no-interaction
fi

exec docker-php-entrypoint "$@"
