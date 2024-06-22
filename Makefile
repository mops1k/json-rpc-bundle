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

application:
	docker compose exec application sh

composer:
	docker compose run composer sh

restart: stop start
