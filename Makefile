all: start

up:
	docker compose up -d

stop:
	docker compose stop

start:
	docker compose start

pint:
	docker compose exec application sh -c "php vendor/bin/pint"

test:
	docker compose exec application sh -c "php vendor/bin/phpunit"

coverage:
	docker compose exec application sh -c "php vendor/bin/phpunit --coverage-html=var/coverage"

psalm:
	docker compose exec application sh -c "php vendor/bin/psalm"

application:
	docker compose exec application sh

composer:
	docker compose run composer sh

permissions:
	docker compose exec application sh -c "chown 1000:1000 -R /app/"

restart: stop start
