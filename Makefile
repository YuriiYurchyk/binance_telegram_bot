up:
	DOCKER_BUILDKIT=1 COMPOSE_DOCKER_CLI_BUILD=1 docker-compose up --build --force-recreate -d
	docker ps
	docker-compose exec php-fpm bash

down:
	docker-compose down

r: down up

bash:
	docker-compose exec php-fpm bash

node:
	docker-compose exec node bash

db:
	php artisan binance:run

ps:
	docker-compose ps

helper:
	php artisan ide-helper:generate
	php artisan ide-helper:meta
	php artisan ide-helper:models

perm:
	find . -type f -exec chmod a+rw {} \;
	find . -type d -exec chmod a+rwx {} \;
	chown -R www-data:www-data ./storage
	chown -R www-data:www-data ./bootstrap
	chown -R www-data:www-data ./binance-data

	# sudo chmod 777 ./ -R
	# chown -R $USER:$USER .

#docker-comp