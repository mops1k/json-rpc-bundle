all: start

up:
	docker compose up -d --remove-orphans

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

update:
	docker compose exec application sh -c "composer update"

update-lowest:
	docker compose exec application sh -c "composer update --prefer-lowest"

application:
	docker compose exec application sh

permissions:
	docker compose exec application sh -c "chown 1000:1000 -R /app/"

restart: stop start
